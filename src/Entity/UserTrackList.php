<?php

namespace App\Entity;

use App\Repository\UserTrackListRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserTrackListRepository::class)]
#[ORM\HasLifecycleCallbacks]
class UserTrackList
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToOne(inversedBy: 'userTrackList')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    /**
     * @var Collection<int, MangaUserTrack>|ArrayCollection
     */
    #[ORM\OneToMany(
        mappedBy: 'userTrackList',
        targetEntity: MangaUserTrack::class,
        cascade: ['remove'],
        orphanRemoval: true
    )]
    private Collection|ArrayCollection $mangaUserTracks;

    public function __construct()
    {
        $this->mangaUserTracks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * @return Collection<int, MangaUserTrack>
     */
    public function getMangaUserTracks(): Collection
    {
        return $this->mangaUserTracks;
    }

    public function addMangaUserTrack(MangaUserTrack $mangaUserTrack): static
    {
        if (!$this->mangaUserTracks->contains($mangaUserTrack)) {
            $this->mangaUserTracks->add($mangaUserTrack);
            $mangaUserTrack->setUserTrackList($this);
        }

        return $this;
    }

    public function removeMangaUserTrack(MangaUserTrack $mangaUserTrack): static
    {
        $this->mangaUserTracks->removeElement($mangaUserTrack);
        //        if ($this->mangaUserTracks->removeElement($mangaUserTrack)) {
        //            // set the owning side to null (unless already changed)
        //            if ($mangaUserTrack->getUserTrackList() === $this) {
        //                $mangaUserTrack->setUserTrackList(null);
        //            }
        //        }

        return $this;
    }
}
