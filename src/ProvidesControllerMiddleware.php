<?php

namespace IsraelNogueira\fastRouter;

use IsraelNogueira\fastRouter\ControllerMiddlewareOptions;

interface ProvidesControllerMiddleware
{
    public function middleware($middleware) : ControllerMiddlewareOptions;

    public function getControllerMiddleware() : array;
}
