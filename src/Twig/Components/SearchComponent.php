<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(template: 'components/section/SearchComponent.html.twig')]
final class SearchComponent
{
    public string $placeholder;
    public bool $isToggleAdvanced = false;
    public bool $isToggleAdult = false;
    public string $toggleAdvancedTitle = '';
    public string $toggleAdvancedText = '';
    public string $toggleAdultTitle = '';
    public string $toggleAdultText = '';
}
