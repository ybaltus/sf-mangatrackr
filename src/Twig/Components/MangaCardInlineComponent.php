<?php

namespace App\Twig\Components;

use App\Entity\Manga;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(template: 'components/section/MangaCardInlineComponent.html.twig')]
final class MangaCardInlineComponent
{
    public string $title;

    /**
     * @var array<Manga>
     */
    public array $mangas;

    public bool $isHome = false;
}
