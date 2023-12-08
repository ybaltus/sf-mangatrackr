<?php

namespace App\Twig\Components;

use App\Entity\User;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent()]
final class NavBarComponent
{
    public string $appName;

    public User|null $user;

    /**
     * @var array<string>|string[]
     */
    public array $menus = [
        'home_index' => 'Accueil',
        'catalog_index' => 'Catalogue',
        'scantheque_index' => 'Scanthèque',
        'about_index' => 'À propos',
    ];
}
