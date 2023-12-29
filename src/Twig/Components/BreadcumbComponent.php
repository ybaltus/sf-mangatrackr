<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(template: 'components/layout/BreadcumbComponent.html.twig')]
final class BreadcumbComponent
{
    /**
     * @var array<string>
     */
    public array $menus;
}
