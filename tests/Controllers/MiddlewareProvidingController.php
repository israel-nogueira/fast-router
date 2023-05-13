<?php

namespace IsraelNogueira\fastRouter\Test\Controllers;

use IsraelNogueira\fastRouter\Controller;
use IsraelNogueira\fastRouter\ControllerMiddlewareOptions;
use IsraelNogueira\fastRouter\ProvidesControllerMiddleware;
use IsraelNogueira\fastRouter\Test\Middleware\AddHeaderMiddleware;

class MiddlewareProvidingController extends Controller
{
    public function returnOne()
    {
        return 'One';
    }

    public function returnTwo()
    {
        return 'Two';
    }

    public function returnThree()
    {
        return 'Three';
    }
}
