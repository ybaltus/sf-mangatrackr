<?php

namespace App\EventListener;

use App\Entity\Editor;
use App\Entity\Fantrad;
use App\Entity\Manga;
use App\Entity\MangaStatistic;
use App\Entity\MangaStatus;
use App\Entity\MangaType;
use App\Entity\StatusTrack;
use App\Entity\TextContentPage;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\String\Slugger\SluggerInterface;

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
        StatusTrack::class,
        TextContentPage::class,
    ];

    public function __construct(
        private SluggerInterface $slugger
    ) {
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();

        // Check the entity instance
        $checkEntity = $this->checkInstanceOf($entity);
        if (!$checkEntity) {
            return;
        }
        $this->createSlug($entity);
    }

    /**
     * Create a slug with String component.
     */
    private function createSlug(object $entity): void
    {
        $hasNameSlug = method_exists($entity, 'setNameSlug');
        $hasTitleSlug = method_exists($entity, 'setTitleSlug');

        if (!$hasNameSlug && !$hasTitleSlug) {
            return;
        }

        if ($hasNameSlug) {
            /**
             * @var Editor|MangaType|Fantrad|StatusTrack|TextContentPage $entity
             */
            $entity->setNameSlug($this->slugger->slug($entity->getName())->lower());
        }

        /**
         * @var Manga|MangaStatus $entity
         */
        if ($hasTitleSlug) {
            if ($entity instanceof Manga && $entity->getTitleSlug()) {
                return;
            }
            $entity->setTitleSlug($this->slugger->slug($entity->getTitle())->lower());
        }
    }

    /**
     * Verify if the entity is in the constant ENTITIES.
     */
    private function checkInstanceOf(object $entity): bool
    {
        foreach (self::ENTITIES as $className) {
            if ($entity instanceof $className) {
                return true;
            }
        }

        return false;
    }
}
