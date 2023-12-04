<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent()]
final class GalleryComponent
{
    public mixed $mangas; // array or PaginationInterface

    public bool $isPagination = false;
}
