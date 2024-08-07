<?php

namespace App\Twig\Components;

use App\Entity\User;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(template: 'components/layout/NavBarComponent.html.twig')]
final class NavBarComponent
{
    public string $appName;

    public ?User $user;

    /**
     * @var array<string>|string[]
     */
    public array $menus = [
        'home_index' => 'Accueil',
        'calendar_index' => 'Calendrier',
        'catalog_index' => 'Catalogue',
        'scantheque_index' => 'Scanthèque',
        'about_index' => 'À propos',
    ];
}
