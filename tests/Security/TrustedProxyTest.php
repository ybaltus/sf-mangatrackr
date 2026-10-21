<?php

declare(strict_types=1);

namespace App\Tests\Security;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Derrière le reverse proxy, les en-têtes X-Forwarded-* ne sont crus que depuis TRUSTED_PROXIES (ADR 0016).
 */
class TrustedProxyTest extends WebTestCase
{
    public function testForwardedHeadersAreUsedFromTrustedProxy(): void
    {
        $client = static::createClient();
        $client->request('GET', '/', [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.5',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ]);

        $request = $client->getRequest();
        $this->assertSame('203.0.113.5', $request->getClientIp());
        $this->assertTrue($request->isSecure());
    }

    public function testForwardedHeadersAreIgnoredFromOtherSource(): void
    {
        $client = static::createClient();
        $client->request('GET', '/', [], [], [
            'REMOTE_ADDR' => '198.51.100.7',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.5',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ]);

        $request = $client->getRequest();
        $this->assertSame('198.51.100.7', $request->getClientIp());
        $this->assertFalse($request->isSecure());
    }
}
