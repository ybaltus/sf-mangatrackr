<?php

namespace App\DataFixtures;

use App\Entity\MangaUserTrack;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MangaUserTrackFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $users = [
            $this->getReference(UserFixtures::USERDEFAULT1),
            $this->getReference(UserFixtures::USERDEFAULT2),
            $this->getReference(UserFixtures::USERADMIN),
        ];

        /**
         * @var User $user
         */
        foreach ($users as $key => $user) {
            for ($i = 0; $i < 3; ++$i) {
                $entity = (new MangaUserTrack())
                    ->setManga($this->getReference('manga_'.mt_rand(1, 3)))
                    ->setUserTrackList($user->getUserTrackList())
                    ->setStatusTrack($this->getReference('sTrack_'.mt_rand(1, 3)))
                    ->setNbChapters(mt_rand(1, 150))
                ;

                $manager->persist($entity);
            }
        }
        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            UserFixtures::class,
            MangaFixtures::class,
        ];
    }
}
