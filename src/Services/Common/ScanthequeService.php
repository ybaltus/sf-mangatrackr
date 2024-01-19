<?php

namespace App\Services\Common;

use App\Entity\Manga;
use App\Entity\MangaUserTrack;
use App\Entity\StatusTrack;
use Doctrine\ORM\EntityManagerInterface;

class ScanthequeService
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
    }

    /**
     * @param array<mixed> $mangaDatas
     */
    public function persistMangasDatas(
        StatusTrack $statusTrack,
        mixed $user,
        array $mangaDatas
    ): bool {
        try {
            foreach ($mangaDatas as $mData) {
                // Get Manga Entity
                $manga = $this->em->getRepository(Manga::class)->findOneByTitleSlug($mData['titleSlug']);

                // Get MangaStatusTrack if exist or create a new instance
                $mangaStatusTrack = $this->checkMutEntryInData($mData) ?
                    $this->em->getRepository(MangaUserTrack::class)->findOneById($mData['mut']) :
                    (new MangaUserTrack())
                        ->setUserTrackList($user->getUserTrackList())
                        ->setManga($manga);

                // Update status and nbChapter
                $mangaStatusTrack->setStatusTrack($statusTrack);
                $mangaStatusTrack->setNbChapters($mData['nbChaptersTrack']);

                $this->em->persist($mangaStatusTrack);
                //                dump($mangaStatusTrack);
            }
            $this->em->flush();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param array<mixed> $mangaData
     */
    public function updateMangasData(
        MangaUserTrack $mangaUserTrack,
        array $mangaData,
    ): bool {
        try {
            if (true === $mangaData['isUpdateStatusTrack']) {
                $statusTrack = $this->em->getRepository(StatusTrack::class)
                    ->findOneByNameSlug($mangaData['statusTrack']);
                $mangaUserTrack->setStatusTrack($statusTrack);
            } else {
                $mangaUserTrack->setNbChapters($mangaData['nbChaptersTrack']);
            }
            $this->em->flush();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkMutEntryInData(mixed $mData): bool
    {
        if (!array_key_exists('mut', $mData) || !$mData['mut']) {
            return false;
        } else {
            return is_numeric($mData['mut']);
        }
    }
}
