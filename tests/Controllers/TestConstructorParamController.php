<?php

namespace IsraelNogueira\fastRouter\Test\Controllers;

use IsraelNogueira\fastRouter\Test\Services\TestService;

class TestConstructorParamController
{
    private $testService;

    public function __construct(TestService $testService)
    {
        $this->testService = $testService;
    }

    public function returnTestServiceValue()
    {
        return $this->testService->value;
    }
}
