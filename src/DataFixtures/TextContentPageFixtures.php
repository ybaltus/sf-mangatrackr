<?php

namespace App\DataFixtures;

use App\Entity\TextContentPage;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class TextContentPageFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Add terms of use
        $this->addTermsOfUse($manager);
    }

    private function addTermsOfUse(ObjectManager $manager): void
    {
        $faker = Factory::create();

        $terms = (new TextContentPage())
            ->setName('Terms of use')
            ->setContent($faker->text(500))
        ;

        $manager->persist($terms);
        $manager->flush();
    }
}
