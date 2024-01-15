<?php

namespace App\Tests\Command;

interface CommandTestInterface
{
    public const USER_DEFAULT = 'user@default1.com';

    public function testSucessfulExecution(): void;

    public function testExecutionFailed(): void;
}
