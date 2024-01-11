<?php

namespace App\Tests\Controller;

interface ControllerTestInterface
{
    /**
     * Test Response HTTP Status = 200.
     */
    public function testHTTPResponseSuccess(): void;

    /**
     * Test Response HTTP Status != 200.
     */
    public function testHTTPResponseFailed(): void;
}
