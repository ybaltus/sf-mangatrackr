<?php

namespace App\Tests\Controller;

interface ControllerTestInterface
{
    public const TEST_USER_EMAIL = 'user@default1.com';

    /**
     * Test Response HTTP Status = 200.
     */
    public function testHTTPResponseSuccess(): void;

    /**
     * Test Response HTTP Status != 200.
     */
    public function testHTTPResponseFailed(): void;
}
