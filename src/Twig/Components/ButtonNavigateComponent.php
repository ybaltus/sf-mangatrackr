<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(template: 'components/button/ButtonNavigateComponent.html.twig')]
final class ButtonNavigateComponent
{
    public string $title;
    public string $routeName;

    public string $direction = 'left';
}
