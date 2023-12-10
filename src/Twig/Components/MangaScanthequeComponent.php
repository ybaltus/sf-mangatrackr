<?php

namespace App\Twig\Components;

use App\Entity\Manga;
use App\Entity\User;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class MangaScanthequeComponent
{
    public string $title;
    public User|null $user;
    public string $statusTrack;
}
