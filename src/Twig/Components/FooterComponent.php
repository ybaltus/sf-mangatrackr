<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(template: 'components/layout/FooterComponent.html.twig')]
final class FooterComponent
{
    public string $appName;
}
