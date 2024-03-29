<?php

namespace App\DataFixtures;

use App\Entity\Manga;
use App\Entity\MangaJikanAPI;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class MangaFixtures extends DataFixturesAbstract implements DependentFixtureInterface
{
    public const LIST_MANGAS = [
        'One piece',
        'Naruto',
        'Bleach',
        'Dragon Ball Z',
    ];

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        foreach (self::LIST_MANGAS as $key => $name) {
            $entity = (new Manga())
                ->setTitle($name)
                ->setTitleAlternative('アオタ ミノル')
                ->setAuthor($faker->name())
                ->setDescription($faker->paragraph())
                ->setDesigner($faker->name())
                ->setMangaStatus($this->getReference('mStatus_ref_'.mt_rand(1, 3)))
                ->setPublishedAt(new \DateTimeImmutable())
                ->setNbChapters(mt_rand(1, 300))
                ->addEditor($this->getReference('editor_ref_'.mt_rand(1, 3)))
                ->addEditor($this->getReference('editor_ref_'.mt_rand(1, 3)))
                ->addMangaType($this->getReference('mType_ref_'.mt_rand(1, 3)))
                ->addMangaType($this->getReference('mType_ref_'.mt_rand(1, 3)))
                ->addFantrad($this->getReference('fantrad_ref_'.mt_rand(1, 3)))
                ->addFantrad($this->getReference('fantrad_ref_'.mt_rand(1, 3)))
            ;

            $manager->persist($entity);

            // Edit MangaStatistic after the MangaEntityListener
            $mgStatistic = $entity->getMangaStatistic();
            $mgStatistic->setRating(2);
            $mgStatistic->setNbTrack(mt_rand(1, 10));
            $mgStatistic->setNbView(mt_rand(100, 200));

            // Edit MangaJikanAPI after the MangaEntityListener
            $mangaJikanAPI = (new MangaJikanAPI())
            ->setManga($entity)
            ;
            $entity->setMangaJikanAPI($mangaJikanAPI);
            $mgJikanAPI = $entity->getMangaJikanAPI();
            $mgJikanAPI->setMalImgJpgLarge('https://cdn.myanimelist.net/images/manga/2/253146l.jpg');
            $mgJikanAPI->setMalImgWebpLarge('https://cdn.myanimelist.net/images/manga/2/253146l.jpg');
            $mgJikanAPI->setMalAuthors(['Echiiro Oda']);

            $this->saveReferences($entity, $key, 'manga');
        }
        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            EditorFixtures::class,
            MangaStatusFixtures::class,
            FantradFixtures::class,
        ];
    }
}
