<?php
namespace App\Service;

use App\Entity\User;
use App\Repository\WebauthnCredentialRepository;
use App\Repository\UserRepository;
use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\ECDSA\ES384;
use Cose\Algorithm\Signature\ECDSA\ES512;
use Cose\Algorithm\Signature\RSA\RS256;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\TokenBinding\IgnoreTokenBindingHandler;
use Symfony\Component\HttpFoundation\RequestStack;

class PasskeyAuthService
{
    private string $rpId;
    private string $rpName;
    private string $origin;

    public function __construct(
        private RequestStack $requestStack,
        private WebauthnCredentialRepository $credRepo,
        private UserRepository $userRepo
    ) {
        $this->rpId   = 'localhost';
        $this->rpName = 'EventRes';
        $this->origin = 'http://localhost';
    }

    // ─── Registration ────────────────────────────────────────────────────────

    public function getRegistrationOptions(User $user): array
    {
        $rp = PublicKeyCredentialRpEntity::create($this->rpName, $this->rpId);

        $userEntity = PublicKeyCredentialUserEntity::create(
            $user->getEmail(),
            (string) $user->getId(),
            $user->getUsername() ?? $user->getEmail()
        );

        $challenge = random_bytes(32);

        $pubKeyParams = [
            PublicKeyCredentialParameters::create('public-key', -7),   // ES256
            PublicKeyCredentialParameters::create('public-key', -257), // RS256
        ];

        $existingCredentials = array_map(
            fn($cred) => PublicKeyCredentialDescriptor::create(
                'public-key',
                base64_decode($cred->getCredentialId())
            ),
            $this->credRepo->findByUser($user)
        );

        $options = PublicKeyCredentialCreationOptions::create(
            $rp,
            $userEntity,
            $challenge,
            $pubKeyParams
        )
        ->excludeCredentials(...$existingCredentials)
        ->setTimeout(60000);

        // Store in session for verify step
        $this->requestStack->getSession()->set(
            'webauthn_registration',
            base64_encode(serialize($options))
        );

        return $this->serializeCreationOptions($options, $challenge);
    }

    public function verifyRegistration(string $responseJson, User $user): void
    {
        $sessionData = $this->requestStack->getSession()->get('webauthn_registration');
        if (!$sessionData) {
            throw new \RuntimeException('Registration session expired. Please try again.');
        }

        $options = unserialize(base64_decode($sessionData));
        $data    = json_decode($responseJson, true);
        $cred    = $data['credential'] ?? $data;

        // Decode the client data to verify origin & type
        $clientDataJSON = base64_decode($this->base64urlDecode($cred['response']['clientDataJSON']));
        $clientData     = json_decode($clientDataJSON, true);

        if ($clientData['type'] !== 'webauthn.create') {
            throw new \RuntimeException('Invalid type in clientDataJSON');
        }
        if ($clientData['origin'] !== $this->origin) {
            throw new \RuntimeException('Origin mismatch: ' . $clientData['origin']);
        }

        // Save the credential
        $credentialId = $cred['rawId'];
        $publicKey    = $cred['response']['attestationObject']; // store raw for now

        $this->credRepo->saveCredential($user, $credentialId, $publicKey);

        $this->requestStack->getSession()->remove('webauthn_registration');
    }

    // ─── Login ───────────────────────────────────────────────────────────────

    public function getLoginOptions(): array
    {
        $challenge = random_bytes(32);

        $options = PublicKeyCredentialRequestOptions::create($challenge)
            ->setRpId($this->rpId)
            ->setTimeout(60000)
            ->setUserVerification(
                PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_PREFERRED
            );

        $this->requestStack->getSession()->set(
            'webauthn_login',
            base64_encode(serialize(['challenge' => base64_encode($challenge)]))
        );

        return [
            'challenge'        => $this->base64urlEncode($challenge),
            'timeout'          => 60000,
            'rpId'             => $this->rpId,
            'userVerification' => 'preferred',
            'allowCredentials' => [],
        ];
    }

    public function verifyLogin(string $responseJson): User
    {
        $sessionData = $this->requestStack->getSession()->get('webauthn_login');
        if (!$sessionData) {
            throw new \RuntimeException('Login session expired. Please try again.');
        }

        $session = unserialize(base64_decode($sessionData));
        $data    = json_decode($responseJson, true);
        $cred    = $data['credential'] ?? $data;

        // Verify clientDataJSON
        $clientDataJSON = base64_decode($this->base64urlDecode($cred['response']['clientDataJSON']));
        $clientData     = json_decode($clientDataJSON, true);

        if ($clientData['type'] !== 'webauthn.get') {
            throw new \RuntimeException('Invalid type');
        }
        if ($clientData['origin'] !== $this->origin) {
            throw new \RuntimeException('Origin mismatch');
        }

        $challengeFromClient = $this->base64urlDecode($clientData['challenge']);
        if ($challengeFromClient !== $session['challenge']) {
            throw new \RuntimeException('Challenge mismatch');
        }

        // Find credential by ID
        $credentialId = $cred['rawId'];
        $entity       = $this->credRepo->findByCredentialId($credentialId);

        if (!$entity) {
            throw new \RuntimeException('Passkey not recognized');
        }

        $this->requestStack->getSession()->remove('webauthn_login');

        return $entity->getUser();
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function base64urlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64urlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
    }

    private function serializeCreationOptions(
        PublicKeyCredentialCreationOptions $options,
        string $rawChallenge
    ): array {
        return [
            'challenge' => $this->base64urlEncode($rawChallenge),
            'rp'        => ['name' => $this->rpName, 'id' => $this->rpId],
            'user'      => [
                'id'          => $this->base64urlEncode($options->user->id),
                'name'        => $options->user->name,
                'displayName' => $options->user->displayName,
            ],
            'pubKeyCredParams'        => [
                ['type' => 'public-key', 'alg' => -7],
                ['type' => 'public-key', 'alg' => -257],
            ],
            'timeout'                 => 60000,
            'excludeCredentials'      => [],
            'authenticatorSelection'  => [
                'userVerification'   => 'preferred',
                'residentKey'        => 'preferred',
            ],
            'attestation' => 'none',
        ];
    }
}
