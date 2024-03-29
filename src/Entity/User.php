<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity('email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length([
        'min' => 2,
        'max' => 50,
    ])]
    private string $username;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\Email()]
    #[Assert\Length([
        'min' => 2,
        'max' => 180,
    ])]
    private string $email;

    /**
     * @var array<int, string>
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string|null The plain password
     */
    private ?string $plainPassword = '';

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    #[Assert\NotBlank]
    private string $password = 'password';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    #[Assert\Type('boolean')]
    private bool $isActivated = true;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private UserNews $userNews;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private UserTrackList $userTrackList;

    /**
     * Used to initiate the datas for the localstorage
     * return Json datas.
     */
    private string $scanthequeData = '';

    /**
     * @var ArrayCollection|Collection<int, UserResetPassword>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserResetPassword::class, orphanRemoval: true)]
    private Collection $userResetPasswords;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $idGoogle = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastConnectedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->userResetPasswords = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param array<int, string> $roles
     *
     * @return $this
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
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

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getUserNews(): UserNews
    {
        return $this->userNews;
    }

    public function setUserNews(UserNews $userNews): static
    {
        // set the owning side of the relation if necessary
        if ($userNews->getUser() !== $this) {
            $userNews->setUser($this);
        }

        $this->userNews = $userNews;

        return $this;
    }

    public function getUserTrackList(): UserTrackList
    {
        return $this->userTrackList;
    }

    public function setUserTrackList(UserTrackList $userTrackList): static
    {
        // set the owning side of the relation if necessary
        if ($userTrackList->getUser() !== $this) {
            $userTrackList->setUser($this);
        }

        $this->userTrackList = $userTrackList;

        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getScanthequeData(): string
    {
        $listMangasTrack = $this->getUserTrackList()->getMangaUserTracks();

        if (0 === count($listMangasTrack)) {
            return $this->scanthequeData;
        }

        $results = [
            'play' => [],
            'pause' => [],
            'archived' => [],
            'not-started' => [],
        ];

        /**
         * @var MangaUserTrack $mangaTrack
         */
        foreach ($listMangasTrack as $mangaTrack) {
            if (true === $mangaTrack->isIsActivated()) {
                $manga = $mangaTrack->getManga();
                // Set UrlImg
                if ($manga->getMangaJikanAPI()) {
                    $urlImg = $manga->getMangaJikanAPI()->getMalImgWebp();
                } elseif ($manga->getMangaMangaUpdatesAPI()) {
                    $urlImg = $manga->getMangaMangaUpdatesAPI()->getMuImgJpg();
                } else {
                    $urlImg = $manga->getUrlImg();
                }
                $results[$mangaTrack->getStatusTrack()->getNameSlug()][$manga->getTitleSlug()] = [
                    'nbChapters' => $manga->getNbChapters(),
                    'nbChaptersTrack' => $mangaTrack->getNbChapters(),
                    'statusTrack' => $mangaTrack->getStatusTrack()->getNameSlug(),
                    'title' => $manga->getTitle(),
                    'titleSlug' => $manga->getTitleSlug(),
                    'urlImg' => $urlImg,
                    'mut' => $mangaTrack->getId(),
                ];
            }
        }

        return json_encode($results);
    }

    /**
     * @return Collection<int, UserResetPassword>
     */
    public function getUserResetPasswords(): Collection
    {
        return $this->userResetPasswords;
    }

    public function addUserResetPassword(UserResetPassword $userResetPassword): static
    {
        if (!$this->userResetPasswords->contains($userResetPassword)) {
            $this->userResetPasswords->add($userResetPassword);
            $userResetPassword->setUser($this);
        }

        return $this;
    }

    public function removeUserResetPassword(UserResetPassword $userResetPassword): static
    {
        $this->userResetPasswords->removeElement($userResetPassword);

        return $this;
    }

    public function getIdGoogle(): ?string
    {
        return $this->idGoogle;
    }

    public function setIdGoogle(?string $idGoogle): static
    {
        $this->idGoogle = $idGoogle;

        return $this;
    }

    public function getLastConnectedAt(): ?\DateTimeImmutable
    {
        return $this->lastConnectedAt;
    }

    public function setLastConnectedAt(?\DateTimeImmutable $lastConnectedAt): static
    {
        $this->lastConnectedAt = $lastConnectedAt;

        return $this;
    }
}
