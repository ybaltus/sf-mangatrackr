<?php

namespace App\Services\Command;

use App\Entity\Fantrad;
use App\Entity\MangaStatus;
use App\Entity\MangaType;
use App\Entity\StatusTrack;
use App\Repository\FantradRepository;
use App\Repository\MangaStatusRepository;
use App\Repository\MangaTypeRepository;
use App\Repository\StatusTrackRepository;
use App\Services\Trait\SampleDataTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class InitDataService
{
    use SampleDataTrait;

    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    public function initAllDatas(): void
    {
        // MangaTypes
        foreach ($this->getMangaTypes() as $value) {
            $entity = $this->verifyIfExistInDb(MangaType::class, $value);
            if (!$entity) {
                $entity = (new MangaType())
                    ->setName($value)
                ;
                $this->em->persist($entity);
            }
        }

        // MangaStatus
        foreach ($this->getMangaStatus() as $value) {
            $entity = $this->verifyIfExistInDb(MangaStatus::class, $value, true);

            if (!$entity) {
                $entity = (new MangaStatus())
                    ->setTitle($value)
                ;
                $this->em->persist($entity);
            }
        }

        // Fantrad
        foreach ($this->getFantrad() as $value) {
            $entity = $this->verifyIfExistInDb(Fantrad::class, $value[0]);
            if (!$entity) {
                $entity = (new Fantrad())
                    ->setName($value[0])
                    ->setUrl($value[1])
                    ->setLanguage($value[2])
                ;
                $this->em->persist($entity);
            }
        }

        // StatusTrack
        foreach ($this->getStatusTrack() as $value) {
            $entity = $this->verifyIfExistInDb(StatusTrack::class, $value);
            if (!$entity) {
                $entity = (new StatusTrack())
                    ->setName($value)
                ;
                $this->em->persist($entity);
            }
        }

        $this->em->flush();
    }

    private function verifyIfExistInDb(string $className, string $value, bool $isTitle = false): bool|object
    {
        $slugger = new AsciiSlugger();
        $valSlug = $slugger->slug($value)->lower();
        /**
         * @var MangaTypeRepository|MangaStatusRepository|StatusTrackRepository|FantradRepository $repository
         */
        $repository = $this->em->getRepository($className); /** @phpstan-ignore-line */
        $entity = $isTitle ?
            $repository->findOneByTitleSlug($valSlug) :
            $repository->findOneByNameSlug($valSlug)
        ;
        if ($entity) {
            return $entity;
        }

        return false;
    }
}
