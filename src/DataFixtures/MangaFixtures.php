<?php

namespace App\DataFixtures;

use App\Entity\Manga;
use App\Entity\MangaStatistic;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class MangaFixtures extends Fixture implements DependentFixtureInterface
{
    public const REFOBJ1 = 'manga_1';
    public const REFOBJ2 = 'manga_2';
    public const REFOBJ3 = 'manga_3';

    public const LIST_MANGAS = [
        'One piece',
        'Naruto',
        'Bleach',
        'Dragon Ball Z'
    ];

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        foreach (self::LIST_MANGAS as $key => $name) {
            $entity = (new Manga())
                ->setTitle($name)
                ->setTitleAlternative("アオタ ミノル")
                ->setAuthor($faker->name())
                ->setDescription($faker->paragraph())
                ->setDesigner($faker->name())
                ->setMangaStatus($this->getReference('mStatus_'.mt_rand(1, 3)))
                ->setPublishedAt(new \DateTimeImmutable())
                ->setNbChapters(mt_rand(1,300))
                ->addEditor($this->getReference('editor_'.mt_rand(1, 3)))
                ->addEditor($this->getReference('editor_'.mt_rand(1, 3)))
                ->addMangaType($this->getReference('mType_'.mt_rand(1, 3)))
                ->addMangaType($this->getReference('mType_'.mt_rand(1, 3)))
                ->addFantrad($this->getReference('fantrad_'.mt_rand(1, 3)))
                ->addFantrad($this->getReference('fantrad_'.mt_rand(1, 3)))
            ;

            // Add MangaStatistic
            $mangaStatistic = (new MangaStatistic())
                ->setRating(2)
                ->setNbTrack(mt_rand(1,10))
                ->setNbView(mt_rand(100,200))
            ;
            $entity->setMangaStatistic($mangaStatistic);

            $manager->persist($entity);
            $this->saveReferences($entity, $key);
        }
        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            EditorFixtures::class,
            MangaStatusFixtures::class,
            FantradFixtures::class
        ];
    }

    private function saveReferences(object $entity, int $key): void
    {
        $targetReference = match ($key) {
            0 => self::REFOBJ1,
            1 => self::REFOBJ2,
            2 => self::REFOBJ3,
            default => false
        };

        if (!$targetReference) {
            return;
        }

        $this->addReference($targetReference, $entity);
    }

}

