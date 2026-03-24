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

    public function saveCredential(User $user, string $credentialId, string $publicKey): void
    {
        $cred = new WebauthnCredential();
        $cred->setUser($user);
        $cred->setCredentialId($credentialId);
        $cred->setPublicKey($publicKey);
        $cred->setCreatedAt(new \DateTimeImmutable());

        $this->_em->persist($cred);
        $this->_em->flush();
    }

    public function findByCredentialId(string $credentialId): ?WebauthnCredential
    {
        return $this->findOneBy(['credentialId' => $credentialId]);
    }

    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user]);
    }
}
