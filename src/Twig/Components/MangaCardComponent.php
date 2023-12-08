<?php

namespace App\Twig\Components;

use App\Entity\Manga;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class MangaCardComponent
{
    public bool $isHome = false;

    public Manga $manga;

    public int $key = 0;
}
