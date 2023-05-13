<?php

namespace IsraelNogueira\fastRouter;

use Psr\Http\Server\MiddlewareInterface;
use IsraelNogueira\fastRouter\ControllerMiddlewareOptions;

class ControllerMiddleware
{
    /**
     * @var Psr\Http\Server\MiddlewareInterface
     */
    protected $middleware;

    /**
     * @var IsraelNogueira\fastRouter\ControllerMiddlewareOptions
     */
    protected $options;

    /**
     * Constructor
     *
     * @param Psr\Http\Server\MiddlewareInterface|array $middleware
     * @param IsraelNogueira\fastRouter\ControllerMiddlewareOptions $options
     */
    public function __construct($middleware, ControllerMiddlewareOptions $options)
    {
        $this->middleware = $middleware;
        $this->options = $options;
    }

    /**
     * Get the Middleware
     *
     * @return Psr\Http\Server\MiddlewareInterface|string
     */
    public function middleware()
    {
        return $this->middleware;
    }

    /**
     * Get the ControllerMiddlewareOptions
     *
     * @return IsraelNogueira\fastRouter\ControllerMiddlewareOptions
     */
    public function options() : ControllerMiddlewareOptions
    {
        return $this->options;
    }

    /**
     * Is a specific method excluded by the options set on this object
     *
     * @param  string $method
     * @return bool
     */
    public function excludedForMethod($method) : bool
    {
        return $this->options->excludedForMethod($method);
    }
}
