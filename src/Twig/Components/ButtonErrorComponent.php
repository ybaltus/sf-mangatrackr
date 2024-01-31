<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(template: 'components/button/ButtonErrorComponent.html.twig')]
final class ButtonErrorComponent
{
    public string $title;

    public string $pathLink = '';
}
