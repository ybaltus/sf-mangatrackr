<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(template: 'components/card/MangaCardComponent.html.twig')]
final class MangaCardComponent
{
    public bool $isHome = false;

    public mixed $manga;

    public int $key = 0;

    public bool $isOnlyTemplate = false;
}
