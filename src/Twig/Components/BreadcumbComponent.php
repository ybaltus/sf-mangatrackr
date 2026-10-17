<?php

declare(strict_types=1);

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(template: 'components/layout/BreadcumbComponent.html.twig')]
final class BreadcumbComponent
{
    /**
     * @var array<string>
     */
    public array $menus;

    public bool $isCenter = true;
}
