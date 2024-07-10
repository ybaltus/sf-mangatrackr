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
        'home_index' => 'app.home',
        'calendar_index' => 'app.calendar',
        'catalog_index' => 'app.catalog',
        'scantheque_index' => 'app.scantheque',
        'about_index' => 'app.about',
    ];
}
