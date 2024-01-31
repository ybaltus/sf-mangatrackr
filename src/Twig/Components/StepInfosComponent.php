<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(template: 'components/message/StepInfosComponent.html.twig')]
final class StepInfosComponent
{
    /**
     * @var array<string>
     */
    public array $titles;

    /**
     * @var array<string>
     */
    public array $contents;

    public string $idStep;
}
