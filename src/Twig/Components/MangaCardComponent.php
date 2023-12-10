<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class MangaCardComponent
{
    public bool $isHome = false;

    public mixed $manga;

    public int $key = 0;

    public bool $isOnlyTemplate = false;
}
