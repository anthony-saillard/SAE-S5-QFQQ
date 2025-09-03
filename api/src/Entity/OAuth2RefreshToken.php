<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class OAuth2RefreshToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "SEQUENCE")]
    #[ORM\SequenceGenerator(sequenceName: "oauth2_refresh_token_seq")]
    #[ORM\Column]
    /** @phpstan-ignore-next-line */
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $token = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Users $user = null;

    #[ORM\Column(type: "datetime_immutable")]
    private ?\DateTimeImmutable $expiresAt = null;

    public function getId(): ?int
{
    return $this->id;
}

    public function getToken(): ?string
{
    return $this->token;
}

    public function setToken(string $token): self
{
    $this->token = $token;
    return $this;
}

    public function getUser(): ?Users
{
    return $this->user;
}

    public function setUser(?Users $user): self
{
    $this->user = $user;
    return $this;
}

    public function getExpiresAt(): ?\DateTimeImmutable
{
    return $this->expiresAt;
}

    public function setExpiresAt(\DateTimeInterface $expiresAt): self
{
    $this->expiresAt = \DateTimeImmutable::createFromInterface($expiresAt);
    return $this;
}

    public function isExpired(): bool
{
    return $this->expiresAt < new \DateTimeImmutable();
}
}