<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class FlashesMessagesComponent
{
    /**
     * @var array<string>
     */
    public array $flashesMessageTarget = [];

    public string $flashMessageType = 'default';

    /**
     * @var array<string>
     */
    public array $listTypeFlashMessage = ['info', 'warning', 'danger', 'success', 'default'];
}
