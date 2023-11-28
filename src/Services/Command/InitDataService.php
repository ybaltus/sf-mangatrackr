<?php

namespace App\Services\Command;

use App\Entity\Fantrad;
use App\Entity\MangaStatus;
use App\Entity\MangaType;
use App\Entity\StatusTrack;
use App\Services\Trait\SampleDataTrait;
use Doctrine\ORM\EntityManagerInterface;

class InitDataService
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
            $entity = (new MangaType())
            ->setName($value)
            ;
            $this->em->persist($entity);
        }

        // MangaStatus
        foreach ($this->getMangaStatus() as $value) {
            $entity = (new MangaStatus())
                ->setTitle($value)
            ;
            $this->em->persist($entity);
        }

        // Fantrad
        foreach ($this->getFantrad() as $value) {
            $entity = (new Fantrad())
                ->setName($value[0])
                ->setUrl($value[1])
                ->setLanguage($value[2])
            ;
            $this->em->persist($entity);
        }

        // StatusTrack
        foreach ($this->getStatusTrack() as $value) {
            $entity = (new StatusTrack())
                ->setName($value)
            ;
            $this->em->persist($entity);
        }

        $this->em->flush();
    }
}
