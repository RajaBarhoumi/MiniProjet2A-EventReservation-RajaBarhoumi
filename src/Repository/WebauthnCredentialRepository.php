<?php
namespace App\Repository;

use App\Entity\User;
use App\Entity\WebauthnCredential;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Webauthn\PublicKeyCredentialSource;

class WebauthnCredentialRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WebauthnCredential::class);
    }

    public function saveCredential(
        User $user,
        PublicKeyCredentialSource $source,
        string $name = 'My Passkey'
    ): WebauthnCredential {
        $credential = new WebauthnCredential();
        $credential->setUser($user);
        $credential->setName($name);
        $credential->setCredentialSource($source);

        $this->getEntityManager()->persist($credential);
        $this->getEntityManager()->flush();

        return $credential;
    }

    public function findByCredentialId(string $credentialId): ?WebauthnCredential
    {
        $all = $this->findAll();
        foreach ($all as $credential) {
            try {
                $source = $credential->getCredentialSource();
                if ($source->getPublicKeyCredentialId() === $credentialId) {
                    return $credential;
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        return null;
    }
}