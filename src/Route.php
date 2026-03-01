<?php

declare(strict_types=1);

namespace IsraelNogueira\FastRouter;

use Closure;
use IsraelNogueira\FastRouter\Exceptions\RouterException;

/**
 * Route - Represents a single route
 * 
 * @package IsraelNogueira\FastRouter
 * @author Israel Nogueira <israel@feats.com>
 * @license GPL-3.0-or-later
 */
class Route
{
    /**
     * @param array<string> $methods HTTP methods
     * @param string $pattern Route pattern
     * @param Closure|array<mixed>|string $callback Route callback
     * @param array<string> $middlewares Middleware list
     * @param string $name Route name
     * @param bool $continue Continue to next route after execution
     */
    public function __construct(
        public readonly array $methods,
        public readonly string $pattern,
        public readonly Closure|array|string $callback,
        public readonly array $middlewares = [],
        public readonly string $name = '',
        public readonly bool $continue = false
    ) {
    }
    
    /**
     * Check if route matches given method
     */
    public function matchesMethod(string $method): bool
    {
        return in_array('ANY', $this->methods) || in_array(strtoupper($method), $this->methods);
    }
    
    /**
     * Match route against URL
     * 
     * @return array{status: bool, params: array<string, mixed>}
     */
    public function match(string $url): array
    {
        $result = RouteParser::match($this->pattern, $url);
        
        return [
            'status' => $result['status'],
            'params' => $result['params']
        ];
    }
    
    /**
     * Execute route callback
     * 
     * @param array<string, mixed> $params
     * @return mixed
     */
    public function execute(array $params = []): mixed
    {
        $callback = $this->callback;
        
        // Handle middleware chain
        if (!empty($this->middlewares)) {
            return MiddlewareHandler::execute(
                $this->middlewares,
                fn($returns) => $this->executeCallback($callback, $params, $returns)
            );
        }
        
        return $this->executeCallback($callback, $params);
    }
    
    /**
     * Execute the actual callback
     * 
     * @param Closure|array<mixed>|string $callback
     * @param array<string, mixed> $params
     * @param array<mixed> $middlewareReturns
     * @return mixed
     */
    private function executeCallback(
        Closure|array|string $callback,
        array $params = [],
        array $middlewareReturns = []
    ): mixed {
        $args = array_values($params);
        
        // Closure
        if ($callback instanceof Closure) {
            return $callback(...$args);
        }
        
        // Array [Class::class, 'method']
        if (is_array($callback)) {
            if (count($callback) === 2) {
                [$class, $method] = $callback;
                
                if (is_string($class)) {
                    $class = new $class();
                }
                
                return $class->{$method}(...$args);
            }
            
            // Multiple callbacks
            $results = [];
            foreach ($callback as $cb) {
                $results[] = $this->executeCallback($cb, $params, $middlewareReturns);
            }
            return $results;
        }
        
        // String "Class@method"
        if (is_string($callback) && strpos($callback, '@') !== false) {
            [$class, $method] = explode('@', $callback, 2);
            $instance = new $class();
            return $instance->{$method}(...$args);
        }
        
        // Function name
        if (is_string($callback) && function_exists($callback)) {
            return $callback(...$args);
        }
        
        throw new RouterException("Invalid callback type");
    }
}
