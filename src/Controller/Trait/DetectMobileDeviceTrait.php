<?php

namespace App\Controller\Trait;

use Symfony\Component\HttpFoundation\Request;

trait DetectMobileDeviceTrait
{
    private function detectMobileDevice(Request $request): bool
    {
        $userAgent = $request->headers->get('User-Agent');

        return match (true) {
            str_contains($userAgent, 'Android') => true,
            str_contains($userAgent, 'iPhone') => true,
            str_contains($userAgent, 'iPad') => true,
            default => false,
        };
    }
}
