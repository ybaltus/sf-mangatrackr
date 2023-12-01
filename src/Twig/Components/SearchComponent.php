<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent()]
final class SearchComponent
{
    public string $placeholder;
    public string $toggleText;
}
