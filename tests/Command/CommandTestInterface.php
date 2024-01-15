<?php

namespace App\Tests\Command;

interface CommandTestInterface
{
    public function testSucessfulExecution(): void;

    public function testExecutionFailed(): void;
}
