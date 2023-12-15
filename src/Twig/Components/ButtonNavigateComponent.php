<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class ButtonNavigateComponent
{
    public string $title;
    public string $routeName;

    public string $direction = 'left';
}
