<?php

namespace App\Entity;

use App\Repository\MangaMangaUpdatesAPIRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MangaMangaUpdatesAPIRepository::class)]
class MangaMangaUpdatesAPI
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'mangaMangaUpdatesAPI')]
    #[ORM\JoinColumn(nullable: false)]
    private Manga $manga;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Type('string')]
    #[Assert\Length(
        max: 255
    )]
    private ?string $muSeriesId = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 1000
    )]
    private ?string $muDescription = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Type('string')]
    #[Assert\Url(
        protocols: ['http', 'https']
    )]
    private ?string $muUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Type('string')]
    #[Assert\Url(
        protocols: ['http', 'https']
    )]
    private ?string $muImgJpg = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Type('string')]
    #[Assert\Url(
        protocols: ['http', 'https']
    )]
    private ?string $muThumbJpg = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Type('integer')]
    private ?int $muYear = null;

    /**
     * @var string[]|null
     */
    #[ORM\Column(nullable: true)]
    #[Assert\Type('array')]
    private ?array $muGenres = null;

    #[ORM\Column(nullable: true)]
    #[Assert\DateTime]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getMuSeriesId(): ?string
    {
        return $this->muSeriesId;
    }

    public function setMuSeriesId(?string $muSeriesId): static
    {
        $this->muSeriesId = $muSeriesId;

        return $this;
    }

    public function getMuDescription(): ?string
    {
        return $this->muDescription;
    }

    public function setMuDescription(?string $muDescription): static
    {
        $this->muDescription = $muDescription;

        return $this;
    }

    public function getMuUrl(): ?string
    {
        return $this->muUrl;
    }

    public function setMuUrl(?string $muUrl): static
    {
        $this->muUrl = $muUrl;

        return $this;
    }

    public function getMuImgJpg(): ?string
    {
        return $this->muImgJpg;
    }

    public function setMuImgJpg(?string $muImgJpg): static
    {
        $this->muImgJpg = $muImgJpg;

        return $this;
    }

    public function getMuThumbJpg(): ?string
    {
        return $this->muThumbJpg;
    }

    public function setMuThumbJpg(?string $muThumbJpg): static
    {
        $this->muThumbJpg = $muThumbJpg;

        return $this;
    }

    public function getMuYear(): ?int
    {
        return $this->muYear;
    }

    public function setMuYear(?int $muYear): static
    {
        $this->muYear = $muYear;

        return $this;
    }

    /**
     * @return string[]|null
     */
    public function getMuGenres(): ?array
    {
        return $this->muGenres;
    }

    /**
     * @param string[]|null $muGenres
     */
    public function setMuGenres(?array $muGenres): static
    {
        $this->muGenres = $muGenres;

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
}
