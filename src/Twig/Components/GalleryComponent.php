<?php

namespace App\Twig\Components;

use App\Entity\Manga;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent()]
final class GalleryComponent
{
    /**
     * @var array<Manga>
     */
    public array $mangas;
}
