<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class ToastComponent
{
    public string $message = '';

    public string $typeMessage = 'default'; // success, error, warning or default

    public string $positionYMob = 'top-20'; // top-[rem/px] or bottom-[rem/px]
    public string $positionYDesk = 'top-20'; // top-[rem/px] or bottom-[rem/px]

    public string $positionXMob = 'lg:left-10'; // lg:left-[rem/px] or lg:right-[rem/px]
    public string $positionXDesk = 'lg:right-10'; // lg:left-[rem/px] or lg:right-[rem/px]
}
