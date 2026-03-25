<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken as BaseRefreshToken;

#[ORM\Entity]
#[ORM\Table(name: 'refresh_tokens')]
class RefreshToken extends BaseRefreshToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected int|string|null $id = null; 

    #[ORM\Column(type: 'string', length: 128, unique: true)]
    protected $refreshToken;

    #[ORM\Column(type: 'string', length: 255)]
    protected $username;

    #[ORM\Column(type: 'datetime')]
    protected $valid;

    /**
     * @return int|string|null
     */
    public function getId(): int|string|null
    {
        return $this->id;
    }
}