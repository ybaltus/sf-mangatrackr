<?php

namespace App\Twig\Components;

use App\Entity\User;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(template: 'components/layout/NavBarComponent.html.twig')]
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
        'calendar_index' => 'Calendrier',
        'about_index' => 'À propos',
    ];
}
