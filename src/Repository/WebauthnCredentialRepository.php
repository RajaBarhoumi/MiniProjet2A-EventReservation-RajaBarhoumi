<?php
namespace App\Repository;

use App\Entity\User;
use App\Entity\WebauthnCredential;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WebauthnCredentialRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WebauthnCredential::class);
    }

    public function findByCredentialId(string $credentialId): ?WebauthnCredential
    {
        $all = $this->findAll();
        foreach ($all as $credential) {
            if ($credential->getCredentialId() === $credentialId) {
                return $credential;
            }
        }
        return null;
    }
}