<?php

declare(strict_types=1);

namespace App\Tests\Services\Common;

use App\Services\Common\SearchTermValidator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SearchTermValidatorTest extends KernelTestCase
{
    /**
     * @dataProvider acceptedProvider
     */
    public function testAccepted(string $term): void
    {
        $this->assertCount(0, $this->validator()->validate($term));
    }

    /**
     * @dataProvider rejectedProvider
     */
    public function testRejected(string $term): void
    {
        $this->assertGreaterThan(0, count($this->validator()->validate($term)));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function acceptedProvider(): iterable
    {
        yield 'ascii' => ['one piece'];
        yield 'ascii tiret underscore' => ['dr-stone_2'];
        yield 'accentué' => ['Le Théâtre de Á'];
        yield 'majuscules accentuées' => ['ÉCOLE'];
        yield 'deux-points' => ['Re:Zero'];
        yield 'japonais' => ['進撃の巨人'];
        yield 'accent décomposé' => ["Cafe\u{0301} noir"];
        yield 'ponctuation' => ['Hunter x Hunter (2011)!'];
        yield 'max 50' => [str_repeat('é', 50)];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function rejectedProvider(): iterable
    {
        yield 'trop court' => ['ab'];
        yield 'trop long' => [str_repeat('a', 51)];
        yield 'blanc' => ['   '];
        yield 'joker LIKE %' => ['one%piece'];
        yield 'antislash' => ['one\\piece'];
        yield 'guillemet' => ['one" OR 1=1'];
        yield 'balise' => ['<script>alert(1)</script>'];
        yield 'esperluette' => ['a&b&c'];
        yield 'point-virgule' => ['one;drop'];
        yield 'saut de ligne' => ["one\npiece"];
        yield 'tabulation' => ["one\tpiece"];
        yield 'retour chariot' => ["one\rpiece"];
        yield 'espace insécable' => ["one\u{00A0}piece"];
        yield 'octet nul' => ["one\0piece"];
        yield 'caractère de contrôle' => ["one\x07piece"];
        yield 'UTF-8 invalide' => ["one\xC3\x28piece"];
    }

    private function validator(): SearchTermValidator
    {
        self::bootKernel();

        return static::getContainer()->get(SearchTermValidator::class);
    }
}
