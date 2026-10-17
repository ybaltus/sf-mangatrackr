<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Manga;
use App\Entity\MangaUserTrack;
use App\Entity\StatusTrack;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MangaUserTrackFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $users = [
            $this->getReference('user_ref_1', User::class),
            $this->getReference('user_ref_2', User::class),
            $this->getReference('user_ref_3', User::class),
        ];

        /**
         * @var User $user
         */
        foreach ($users as $key => $user) {
            for ($i = 0; $i < 3; ++$i) {
                $entity = (new MangaUserTrack())
                    ->setManga($this->getReference('manga_ref_'.mt_rand(1, 3), Manga::class))
                    ->setUserTrackList($user->getUserTrackList())
                    ->setStatusTrack($this->getReference('sTrack_ref_'.mt_rand(1, 3), StatusTrack::class))
                    ->setNbChapters(mt_rand(1, 150))
                ;

                $manager->persist($entity);
            }
        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            MangaFixtures::class,
        ];
    }
}
