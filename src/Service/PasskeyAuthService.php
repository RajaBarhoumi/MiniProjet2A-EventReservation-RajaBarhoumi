<?php
namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\WebauthnCredentialRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\Server;

class PasskeyAuthService
{
    public function __construct(
        private Server $webauthnServer,
        private RequestStack $requestStack,
        private WebauthnCredentialRepository $credRepo,
        private UserRepository $userRepo
    ) {}

    public function getRegistrationOptions(User $user): array
    {
        $userEntity = new PublicKeyCredentialUserEntity(
            $user->getEmail(),
            $user->getId()->toBinary(),
            $user->getUsername()
        );

        $options = $this->webauthnServer
            ->generatePublicKeyCredentialCreationOptions($userEntity);

        $this->requestStack->getSession()
            ->set('webauthn_registration', $options);

        return $options->jsonSerialize();
    }

    public function verifyRegistration(string $response, User $user): void
    {
        $options = $this->requestStack->getSession()
            ->get('webauthn_registration');

        $userEntity = new PublicKeyCredentialUserEntity(
            $user->getEmail(),
            $user->getId()->toBinary(),
            $user->getUsername()
        );

        $credential = $this->webauthnServer
            ->loadAndCheckAttestationResponse($response, $options, $userEntity);

        $this->credRepo->saveCredential($user, $credential);
        $this->requestStack->getSession()->remove('webauthn_registration');
    }

    public function getLoginOptions(): array
    {
        $options = $this->webauthnServer
            ->generatePublicKeyCredentialRequestOptions();

        $this->requestStack->getSession()
            ->set('webauthn_login', $options);

        return $options->jsonSerialize();
    }

    public function verifyLogin(string $response): User
    {
        $options = $this->requestStack->getSession()
            ->get('webauthn_login');

        $credential = $this->webauthnServer
            ->loadAndCheckAssertionResponse($response, $options);

        $entity = $this->credRepo->findByCredentialId(
            $credential->getPublicKeyCredentialId()
        );

        $entity->touch();
        $this->requestStack->getSession()->remove('webauthn_login');

        return $entity->getUser();
    }
}
