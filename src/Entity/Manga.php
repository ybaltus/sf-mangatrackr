<?php

namespace App\Entity;

use App\Repository\MangaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
#[ORM\Entity(repositoryClass: MangaRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity('titleSlug')]
class Manga
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank]
    #[Assert\Length(
        min: 2,
        max: 180
    )]
    private string $title;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(
        min: 2,
        max: 180
    )]
    private string $titleSlug;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Blank]
    #[Assert\Length(
        min: 2,
        max: 180
    )]
    private ?string $titleAlternative = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Blank]
    #[Assert\Length(
        max: 500
    )]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?float $nbChapters = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank]
    #[Assert\Length(
        min: 2,
        max: 150
    )]
    private string $author;

    #[ORM\Column(length: 150, nullable: true)]
    #[Assert\Blank]
    #[Assert\Length(
        max: 150
    )]
    private ?string $designer = null;

    #[ORM\Column]
    #[Assert\Type('boolean')]
    private bool $isAdult = false;

    #[ORM\Column(nullable: true)]
    #[Assert\Blank]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    #[Assert\Type('boolean')]
    private bool $isActivated = true;

    #[ORM\ManyToMany(targetEntity: Fantrad::class, inversedBy: 'mangas')]
    private Collection $fantrad;

    #[ORM\ManyToMany(targetEntity: Editor::class, inversedBy: 'mangas')]
    private Collection $editor;

    #[ORM\ManyToMany(targetEntity: MangaType::class, inversedBy: 'mangas')]
    private Collection $mangaType;

    #[ORM\ManyToOne(inversedBy: 'mangas')]
    private MangaStatus $mangaStatus;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private MangaStatistic $mangaStatistic;

    #[ORM\OneToOne(mappedBy: 'manga', cascade: ['persist', 'remove'])]
    private ?MangaJikanAPI $mangaJikanAPI = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->fantrad = new ArrayCollection();
        $this->editor = new ArrayCollection();
        $this->mangaType = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitleSlug(): ?string
    {
        return $this->titleSlug;
    }

    public function setTitleSlug(string $titleSlug): static
    {
        $this->titleSlug = $titleSlug;

        return $this;
    }

    public function getTitleAlternative(): ?string
    {
        return $this->titleAlternative;
    }

    public function setTitleAlternative(?string $titleAlternative): static
    {
        $this->titleAlternative = $titleAlternative;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(string $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getDesigner(): ?string
    {
        return $this->designer;
    }

    public function setDesigner(?string $designer): static
    {
        $this->designer = $designer;

        return $this;
    }

    public function isIsAdult(): ?bool
    {
        return $this->isAdult;
    }

    public function setIsAdult(bool $isAdult): static
    {
        $this->isAdult = $isAdult;

        return $this;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): static
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
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

    public function isIsActivated(): ?bool
    {
        return $this->isActivated;
    }

    public function setIsActivated(bool $isActivated): static
    {
        $this->isActivated = $isActivated;

        return $this;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * @return Collection<int, Fantrad>
     */
    public function getFantrad(): Collection
    {
        return $this->fantrad;
    }

    public function addFantrad(Fantrad $fantrad): static
    {
        if (!$this->fantrad->contains($fantrad)) {
            $this->fantrad->add($fantrad);
        }

        return $this;
    }

    public function removeFantrad(Fantrad $fantrad): static
    {
        $this->fantrad->removeElement($fantrad);

        return $this;
    }

    /**
     * @return Collection<int, Editor>
     */
    public function getEditor(): Collection
    {
        return $this->editor;
    }

    public function addEditor(Editor $editor): static
    {
        if (!$this->editor->contains($editor)) {
            $this->editor->add($editor);
        }

        return $this;
    }

    public function removeEditor(Editor $editor): static
    {
        $this->editor->removeElement($editor);

        return $this;
    }

    /**
     * @return Collection<int, MangaType>
     */
    public function getMangaType(): Collection
    {
        return $this->mangaType;
    }

    public function addMangaType(MangaType $mangaType): static
    {
        if (!$this->mangaType->contains($mangaType)) {
            $this->mangaType->add($mangaType);
        }

        return $this;
    }

    public function removeMangaType(MangaType $mangaType): static
    {
        $this->mangaType->removeElement($mangaType);

        return $this;
    }

    public function getMangaStatus(): ?MangaStatus
    {
        return $this->mangaStatus;
    }

    public function setMangaStatus(?MangaStatus $mangaStatus): static
    {
        $this->mangaStatus = $mangaStatus;

        return $this;
    }

    public function getMangaStatistic(): MangaStatistic
    {
        return $this->mangaStatistic;
    }

    public function setMangaStatistic(MangaStatistic $mangaStatistic): static
    {
        $this->mangaStatistic = $mangaStatistic;

        return $this;
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

    public function getMangaJikanAPI(): ?MangaJikanAPI
    {
        return $this->mangaJikanAPI;
    }

    public function setMangaJikanAPI(MangaJikanAPI $mangaJikanAPI): static
    {
        // set the owning side of the relation if necessary
        if ($mangaJikanAPI->getManga() !== $this) {
            $mangaJikanAPI->setManga($this);
        }

        $this->mangaJikanAPI = $mangaJikanAPI;

        return $this;
    }
}
