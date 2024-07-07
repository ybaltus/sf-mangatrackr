<?php

namespace App\Twig\Components;

use App\Entity\User;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(template: 'components/section/MangaScanthequeComponent.html.twig')]
final class MangaScanthequeComponent
{
    public string $title;
    public ?User $user;
    public string $statusTrack;
}
