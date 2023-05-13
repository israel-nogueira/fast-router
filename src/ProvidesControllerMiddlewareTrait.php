<?php

namespace IsraelNogueira\fastRouter;

use IsraelNogueira\fastRouter\ControllerMiddlewareOptions;

trait ProvidesControllerMiddlewareTrait
{
    /**
     * List of ControllerMiddleware
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * Add Middleware
     *
     * @param  Psr\Http\Server\MiddlewareInterface|array $middleware
     * @return IsraelNogueira\fastRouter\ControllerMiddlewareOptions
     */
    public function middleware($middleware) : ControllerMiddlewareOptions
    {
        if (!is_array($middleware)) {
            $middleware = [$middleware];
        }

        $options = new ControllerMiddlewareOptions;

        foreach ($middleware as $m) {
            $this->middleware[] = new ControllerMiddleware($m, $options);
        }

        return $options;
    }

    /**
     * Get the array of ControllerMiddleware
     *
     * @return array
     */
    public function getControllerMiddleware(): array
    {
        return $this->middleware;
    }
}
