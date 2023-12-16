<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
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
