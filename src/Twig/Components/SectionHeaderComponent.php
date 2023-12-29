<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(template: 'components/section/SectionHeaderComponent.html.twig')]
final class SectionHeaderComponent
{
    public string $title;
}
