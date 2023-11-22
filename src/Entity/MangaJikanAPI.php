<?php

namespace App\Entity;

use App\Repository\MangaJikanAPIRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MangaJikanAPIRepository::class)]
final class MangaJikanAPI
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Type('integer')]
    private ?int $malId = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Type('string')]
    private ?string $malDescription = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Type('string')]
    #[Assert\Url(
        protocols: ['http', 'https']
    )]
    private ?string $malUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Type('string')]
    #[Assert\Url(
        protocols: ['http', 'https']
    )]
    private ?string $malImgJpg = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Type('string')]
    #[Assert\Url(
        protocols: ['http', 'https']
    )]
    private ?string $malImgJpgLarge = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Type('string')]
    #[Assert\Url(
        protocols: ['http', 'https']
    )]
    private ?string $malImgWebp = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Type('string')]
    #[Assert\Url(
        protocols: ['http', 'https']
    )]
    private ?string $malImgWebpLarge = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Type('integer')]
    private ?int $malChapters = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Type('integer')]
    private ?int $malVolume = null;

    #[ORM\Column(nullable: true)]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $malStartPublishedAt = null;

    #[ORM\Column(nullable: true)]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $malEndPublishedAt = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Type('array')]
    private ?array $malDemographics = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Type('array')]
    private ?array $malGenres = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Type('array')]
    private ?array $malSerializations = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Type('array')]
    private ?array $malAuthors = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Type('float')]
    private ?float $malScored = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Type('integer')]
    private ?int $malScroredBy = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Type('integer')]
    private ?int $malRank = null;

    #[ORM\Column(nullable: true)]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToOne(inversedBy: 'mangaJikanAPI')]
    #[ORM\JoinColumn(nullable: false)]
    private Manga $manga;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMalId(): ?int
    {
        return $this->malId;
    }

    public function setMalId(?int $malId): static
    {
        $this->malId = $malId;

        return $this;
    }

    public function getMalDescription(): ?string
    {
        return $this->malDescription;
    }

    public function setMalDescription(?string $malDescription): static
    {
        $this->malDescription = $malDescription;

        return $this;
    }

    public function getMalUrl(): ?string
    {
        return $this->malUrl;
    }

    public function setMalUrl(?string $malUrl): static
    {
        $this->malUrl = $malUrl;

        return $this;
    }

    public function getMalImgJpg(): ?string
    {
        return $this->malImgJpg;
    }

    public function setMalImgJpg(?string $malImgJpg): static
    {
        $this->malImgJpg = $malImgJpg;

        return $this;
    }

    public function getMalImgJpgLarge(): ?string
    {
        return $this->malImgJpgLarge;
    }

    public function setMalImgJpgLarge(?string $malImgJpgLarge): static
    {
        $this->malImgJpgLarge = $malImgJpgLarge;

        return $this;
    }

    public function getMalImgWebp(): ?string
    {
        return $this->malImgWebp;
    }

    public function setMalImgWebp(?string $malImgWebp): static
    {
        $this->malImgWebp = $malImgWebp;

        return $this;
    }

    public function getMalImgWebpLarge(): ?string
    {
        return $this->malImgWebpLarge;
    }

    public function setMalImgWebpLarge(?string $malImgWebpLarge): static
    {
        $this->malImgWebpLarge = $malImgWebpLarge;

        return $this;
    }

    public function getMalChapters(): ?int
    {
        return $this->malChapters;
    }

    public function setMalChapters(?int $malChapters): static
    {
        $this->malChapters = $malChapters;

        return $this;
    }

    public function getMalVolume(): ?int
    {
        return $this->malVolume;
    }

    public function setMalVolume(?int $malVolume): static
    {
        $this->malVolume = $malVolume;

        return $this;
    }

    public function getMalStartPublishedAt(): ?\DateTimeImmutable
    {
        return $this->malStartPublishedAt;
    }

    public function setMalStartPublishedAt(?\DateTimeImmutable $malStartPublishedAt): static
    {
        $this->malStartPublishedAt = $malStartPublishedAt;

        return $this;
    }

    public function getMalEndPublishedAt(): ?\DateTimeImmutable
    {
        return $this->malEndPublishedAt;
    }

    public function setMalEndPublishedAt(?\DateTimeImmutable $malEndPublishedAt): static
    {
        $this->malEndPublishedAt = $malEndPublishedAt;

        return $this;
    }

    public function getMalDemographics(): ?array
    {
        return $this->malDemographics;
    }

    public function setMalDemographics(?array $malDemographics): static
    {
        $this->malDemographics = $malDemographics;

        return $this;
    }

    public function getMalGenres(): ?array
    {
        return $this->malGenres;
    }

    public function setMalGenres(?array $malGenres): static
    {
        $this->malGenres = $malGenres;

        return $this;
    }

    public function getMalSerializations(): ?array
    {
        return $this->malSerializations;
    }

    public function setMalSerializations(?array $malSerializations): static
    {
        $this->malSerializations = $malSerializations;

        return $this;
    }

    public function getMalAuthors(): ?array
    {
        return $this->malAuthors;
    }

    public function setMalAuthors(?array $malAuthors): static
    {
        $this->malAuthors = $malAuthors;

        return $this;
    }

    public function getMalScored(): ?float
    {
        return $this->malScored;
    }

    public function setMalScored(?float $malScored): static
    {
        $this->malScored = $malScored;

        return $this;
    }

    public function getMalScroredBy(): ?int
    {
        return $this->malScroredBy;
    }

    public function setMalScroredBy(?int $malScroredBy): static
    {
        $this->malScroredBy = $malScroredBy;

        return $this;
    }

    public function getMalRank(): ?int
    {
        return $this->malRank;
    }

    public function setMalRank(?int $malRank): static
    {
        $this->malRank = $malRank;

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

    public function getManga(): Manga
    {
        return $this->manga;
    }

    public function setManga(Manga $manga): static
    {
        $this->manga = $manga;

        return $this;
    }
}
