<?php

namespace App\Entity;

use App\Repository\UserResetPasswordRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserResetPasswordRepository::class)]
class UserResetPassword
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'userResetPasswords')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(length: 255)]
    private string $resetCode;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $expiredAt;

    #[ORM\Column(length: 255)]
    private string $sendingEmailStatus = '';

    #[ORM\Column]
    private bool $isActivated = true;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->expiredAt = $this->createdAt->add(new \DateInterval('P1D'));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getResetCode(): string
    {
        return $this->resetCode;
    }

    public function setResetCode(string $resetCode): static
    {
        $this->resetCode = $resetCode;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getExpiredAt(): \DateTimeImmutable
    {
        return $this->expiredAt;
    }

    public function setExpiredAt(\DateTimeImmutable $expiredAt): static
    {
        $this->expiredAt = $expiredAt;

        return $this;
    }

    public function getSendingEmailStatus(): ?string
    {
        return $this->sendingEmailStatus;
    }

    public function setSendingEmailStatus(string $sendingEmailStatus): static
    {
        $this->sendingEmailStatus = $sendingEmailStatus;

        return $this;
    }

    public function isIsActivated(): bool
    {
        return $this->isActivated;
    }

    public function setIsActivated(bool $isActivated): static
    {
        $this->isActivated = $isActivated;

        return $this;
    }
}
