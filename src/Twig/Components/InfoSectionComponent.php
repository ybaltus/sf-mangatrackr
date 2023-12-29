<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(template: 'components/section/InfoSectionComponent.html.twig')]
final class InfoSectionComponent
{
    public bool $inline = false;
    public string $title = '';
    public string $info;
    public string $titleBtn = '';
}
