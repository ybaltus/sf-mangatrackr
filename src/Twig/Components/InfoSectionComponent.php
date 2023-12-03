<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent()]
final class InfoSectionComponent
{
    public bool $inline = false;
    public string $title = '';
    public string $info;
    public string $titleBtn = '';
}
