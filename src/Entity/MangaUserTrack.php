<?php

namespace App\Entity;

use App\Repository\MangaUserTrackRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MangaUserTrackRepository::class)]
#[ORM\HasLifecycleCallbacks]
class MangaUserTrack
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    private bool $isActivated = true;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Manga $manga;

    #[ORM\ManyToOne(inversedBy: 'mangaUserTracks')]
    #[ORM\JoinColumn(nullable: false)]
    private UserTrackList $userTrackList;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private StatusTrack $statusTrack;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?float $nbChapters = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

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

    public function getManga(): Manga
    {
        return $this->manga;
    }

    public function setManga(Manga $manga): static
    {
        $this->manga = $manga;

        return $this;
    }

    public function getUserTrackList(): UserTrackList
    {
        return $this->userTrackList;
    }

    public function setUserTrackList(UserTrackList $userTrackList): static
    {
        $this->userTrackList = $userTrackList;

        return $this;
    }

    public function getStatusTrack(): StatusTrack
    {
        return $this->statusTrack;
    }

    public function setStatusTrack(StatusTrack $statusTrack): static
    {
        $this->statusTrack = $statusTrack;

        return $this;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getNbChapters(): ?float
    {
        return $this->nbChapters;
    }

    public function setNbChapters(?float $nbChapters): static
    {
        $this->nbChapters = $nbChapters;

        return $this;
    }
}
