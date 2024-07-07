<?php

namespace App\Entity;

use App\Entity\Enum\MangaCategoryEnum;
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

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(
        min: 2,
        max: 255
    )]
    private ?string $title;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\Length(
        min: 2,
        max: 255
    )]
    private ?string $titleSlug = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(
        min: 2,
        max: 255
    )]
    private ?string $titleAlternative = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 1000
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
    #[Assert\Length(
        max: 150
    )]
    private ?string $designer = null;

    #[ORM\Column]
    #[Assert\Type('boolean')]
    private bool $isAdult = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    #[Assert\Type('boolean')]
    private bool $isActivated = true;

    /**
     * @var ArrayCollection|Collection<int, Fantrad>
     */
    #[ORM\ManyToMany(targetEntity: Fantrad::class, inversedBy: 'mangas')]
    private Collection|ArrayCollection $fantrad;

    /**
     * @var ArrayCollection|Collection<int, Editor>
     */
    #[ORM\ManyToMany(targetEntity: Editor::class, inversedBy: 'mangas', cascade: ['persist'])]
    private Collection|ArrayCollection $editor;

    /**
     * @var ArrayCollection|Collection<int, MangaType>
     */
    #[ORM\ManyToMany(targetEntity: MangaType::class, inversedBy: 'mangas', cascade: ['persist'])]
    private Collection|ArrayCollection $mangaType;

    #[ORM\ManyToOne(inversedBy: 'mangas', cascade: ['persist'])]
    private MangaStatus $mangaStatus;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private MangaStatistic $mangaStatistic;

    #[ORM\OneToOne(mappedBy: 'manga', cascade: ['persist', 'remove'])]
    private ?MangaJikanAPI $mangaJikanAPI = null;

    /**
     * Used to initiate the datas for the localstorage
     * return Json datas.
     */
    private string $scanthequeData = '';

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Type('string')]
    #[Assert\Url(
        protocols: ['http', 'https']
    )]
    private ?string $urlImg = null;

    #[ORM\OneToOne(mappedBy: 'manga', cascade: ['persist', 'remove'])]
    private ?MangaMangaUpdatesAPI $mangaMangaUpdatesAPI = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Type('string')]
    private ?string $titleEnglish = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Type('string')]
    private ?string $titleSynonym = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbVolumes = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastReleasedAt = null;

    #[ORM\Column(length: 100, enumType: MangaCategoryEnum::class, options: ['default' => MangaCategoryEnum::UNKNOWN])]
    private MangaCategoryEnum $category = MangaCategoryEnum::UNKNOWN;

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

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitleSlug(): ?string
    {
        return $this->titleSlug;
    }

    public function setTitleSlug(?string $titleSlug): static
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

    public function getScanthequeData(): string
    {
        if (0 === strlen($this->scanthequeData)) {
            $this->scanthequeData = $this->setScanthequeData();
        }

        return $this->scanthequeData;
    }

    private function setScanthequeData(): string
    {
        // Set UrlImg
        if ($this->getMangaJikanAPI()) {
            $urlImg = $this->getMangaJikanAPI()->getMalImgWebp();
        } elseif ($this->getMangaMangaUpdatesAPI()) {
            $urlImg = $this->getMangaMangaUpdatesAPI()->getMuImgJpg();
        } else {
            $urlImg = $this->getUrlImg();
        }

        return json_encode([
            'title' => $this->title,
            'titleSlug' => $this->titleSlug,
            'urlImg' => $urlImg,
            'nbChapters' => $this->nbChapters,
            'mut' => null,
        ]);
    }

    public function getUrlImg(): ?string
    {
        return $this->urlImg;
    }

    public function setUrlImg(?string $urlImg): static
    {
        $this->urlImg = $urlImg;

        return $this;
    }

    public function __toString(): string
    {
        return $this->title;
    }

    public function getMangaMangaUpdatesAPI(): ?MangaMangaUpdatesAPI
    {
        return $this->mangaMangaUpdatesAPI;
    }

    public function setMangaMangaUpdatesAPI(?MangaMangaUpdatesAPI $mangaMangaUpdatesAPI): static
    {
        // set the owning side of the relation if necessary
        if ($mangaMangaUpdatesAPI->getManga() !== $this) {
            $mangaMangaUpdatesAPI->setManga($this);
        }

        $this->mangaMangaUpdatesAPI = $mangaMangaUpdatesAPI;

        return $this;
    }

    public function getTitleEnglish(): ?string
    {
        return $this->titleEnglish;
    }

    public function setTitleEnglish(?string $titleEnglish): static
    {
        $this->titleEnglish = $titleEnglish;

        return $this;
    }

    public function getTitleSynonym(): ?string
    {
        return $this->titleSynonym;
    }

    public function setTitleSynonym(?string $titleSynonym): static
    {
        $this->titleSynonym = $titleSynonym;

        return $this;
    }

    public function getNbVolumes(): ?int
    {
        return $this->nbVolumes;
    }

    public function setNbVolumes(?int $nbVolumes): static
    {
        $this->nbVolumes = $nbVolumes;

        return $this;
    }

    public function getLastReleasedAt(): ?\DateTimeImmutable
    {
        return $this->lastReleasedAt;
    }

    public function setLastReleasedAt(?\DateTimeImmutable $lastReleasedAt): static
    {
        $this->lastReleasedAt = $lastReleasedAt;

        return $this;
    }

    public function getCategory(): MangaCategoryEnum
    {
        return $this->category;
    }

    public function setCategory(MangaCategoryEnum $category): static
    {
        $this->category = $category;

        return $this;
    }
}
