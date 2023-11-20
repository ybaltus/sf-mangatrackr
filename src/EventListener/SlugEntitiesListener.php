<?php

namespace App\EventListener;

use App\Entity\Editor;
use App\Entity\Fantrad;
use App\Entity\Manga;
use App\Entity\MangaStatistic;
use App\Entity\MangaStatus;
use App\Entity\MangaType;
use App\Entity\StatusTrack;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[AsDoctrineListener(event: Events::prePersist, priority: 500, connection: 'default')]
class SlugEntitiesListener
{
    private const ENTITIES = [
        Editor::class,
        Manga::class,
        MangaType::class,
        MangaStatus::class,
        MangaStatistic::class,
        Fantrad::class,
        StatusTrack::class
    ];

    public function __construct(
        private AsciiSlugger $slugger = new AsciiSlugger()
    )
    {
    }

    public function prePersist(PrePersistEventArgs $args):void
    {
        $entity = $args->getObject();

        // Check the entity instance
        $checkEntity = $this->checkInstanceOf($entity);
        if(!$checkEntity){
            return;
        }
        $this->createSlug($entity);
    }

    /**
     * Create a slug with String component
     * @param object $entity
     * @return void
     */
    private function createSlug(object $entity): void
    {
        $hasNameSlug = method_exists($entity, 'setNameSlug');
        $hasTitleSlug = method_exists($entity, 'setTitleSlug');

        if(!$hasNameSlug && !$hasTitleSlug)
        {
            return;
        }

        if($hasNameSlug)
        {
            $entity->setNameSlug($this->slugger->slug($entity->getName())->lower());
        }

        if($hasTitleSlug)
        {
            $entity->setTitleSlug($this->slugger->slug($entity->getTitle())->lower());
        }
    }

    /**
     * Verify if the entity is in the constant ENTITIES
     * @param object $entity
     * @return bool
     */
    private function checkInstanceOf(object $entity): bool
    {
        foreach (self::ENTITIES as $className){
            if($entity instanceof $className){
                return true;
            }
        }
        return false;
    }
}