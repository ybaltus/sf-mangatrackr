<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class ButtonReturnComponent
{
    public string $title;
    public string $pathReturn;
}
