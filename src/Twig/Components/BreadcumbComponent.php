<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class BreadcumbComponent
{
    /**
     * @var array<string>
     */
    public array $menus;
}
