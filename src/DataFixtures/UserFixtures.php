<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends DataFixturesAbstract
{
    //    public const USERDEFAULT1 = 'user_default1';
    //    public const USERDEFAULT2 = 'user_default2';
    //    public const USERADMIN = 'user_admin';

    public const LIST_USERS = [
        'user@default1.com',
        'user@default2.com',
        'user@admin.com',
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::LIST_USERS as $key => $email) {
            $entity = (new User())
                ->setEmail($email)
                ->setUsername("user_$key")
                ->setPlainPassword('password')
            ;

            if (str_contains($email, 'admin')) {
                $entity->setRoles(['ROLE_ADMIN']);
            }

            $manager->persist($entity);

            $this->saveReferences($entity, $key, 'user');
        }
        $manager->flush();
    }

    //    private function saveReferences(object $entity, int $key): void
    //    {
    //        $targetReference = match ($key) {
    //            0 => self::USERDEFAULT1,
    //            1 => self::USERDEFAULT2,
    //            2 => self::USERADMIN,
    //            default => false
    //        };
    //
    //        if (!$targetReference) {
    //            return;
    //        }
    //
    //        $this->addReference($targetReference, $entity);
    //    }
}
