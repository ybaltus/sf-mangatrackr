<?php

namespace App\Entity;

use App\Repository\MangaStatisticRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MangaStatisticRepository::class)]
#[ORM\HasLifecycleCallbacks]
class MangaStatistic
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?float $rating = null;

    #[ORM\Column]
    #[Assert\Type('integer')]
    #[Assert\PositiveOrZero]
    private int $nbTrack;

    #[ORM\Column]
    #[Assert\Type('integer')]
    #[Assert\PositiveOrZero]
    private int $nbView;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->nbView = 0;
        $this->nbTrack = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRating(): ?float
    {
        return $this->rating;
    }

    public function setRating(?float $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function getNbTrack(): int
    {
        return $this->nbTrack;
    }

    public function setNbTrack(int $nbTrack): static
    {
        $this->nbTrack = $nbTrack;

        return $this;
    }

    public function getNbView(): int
    {
        return $this->nbView;
    }

    public function setNbView(int $nbView): static
    {
        $this->nbView = $nbView;

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

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
