<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(template: 'components/section/GalleryComponent.html.twig')]
final class GalleryComponent
{
    public mixed $mangas; // array or PaginationInterface

    public bool $isPagination = false;
}
