<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(template: 'components/button/ButtonGradientComponent.html.twig')]
final class ButtonGradientComponent
{
    public string $title;

    public string $pathLink = '';
}
