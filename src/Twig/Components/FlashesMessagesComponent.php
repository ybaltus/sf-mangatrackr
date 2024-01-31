<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(template: 'components/message/FlashesMessagesComponent.html.twig')]
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
