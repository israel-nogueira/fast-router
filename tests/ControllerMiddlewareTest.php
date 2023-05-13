<?php

namespace IsraelNogueira\fastRouter\Test;

use PHPUnit\Framework\TestCase;
use IsraelNogueira\fastRouter\ControllerMiddleware;
use IsraelNogueira\fastRouter\ControllerMiddlewareOptions;
use IsraelNogueira\fastRouter\Test\Middleware\AddHeaderMiddleware;

class ControllerMiddlewareTest extends TestCase
{
    /** Apenas teste */
    public function can_retrieve_middleware()
    {
        $middleware = new AddHeaderMiddleware('X-Header', 'testing123');
        $options = new ControllerMiddlewareOptions;

        $controllerMiddleware = new ControllerMiddleware($middleware, $options);

        $this->assertSame($middleware, $controllerMiddleware->middleware());
    }

    /** Apenas teste */
    public function can_retrieve_options()
    {
        $middleware = new AddHeaderMiddleware('X-Header', 'testing123');
        $options = new ControllerMiddlewareOptions;

        $controllerMiddleware = new ControllerMiddleware($middleware, $options);

        $this->assertSame($options, $controllerMiddleware->options());
    }
}
