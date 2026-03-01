<?php

declare(strict_types=1);

namespace IsraelNogueira\FastRouter;

use IsraelNogueira\FastRouter\Exceptions\RouteNotFoundException;

/**
 * Route Collection - Store and retrieve routes
 * 
 * @package IsraelNogueira\FastRouter
 * @author Israel Nogueira <israel@feats.com>
 * @license GPL-3.0-or-later
 */
class RouteCollection
{
    /**
     * @var array<Route>
     */
    private array $routes = [];
    
    /**
     * @var array<string, Route>
     */
    private array $namedRoutes = [];
    
    /**
     * Add route to collection
     */
    public function add(Route $route): void
    {
        $this->routes[] = $route;
        
        if ($route->name !== '') {
            $this->namedRoutes[$route->name] = $route;
        }
    }
    
    /**
     * Get all routes
     * 
     * @return array<Route>
     */
    public function all(): array
    {
        return $this->routes;
    }
    
    /**
     * Find route by name
     */
    public function findByName(string $name): ?Route
    {
        return $this->namedRoutes[$name] ?? null;
    }
    
    /**
     * Get route by name or throw exception
     */
    public function getByName(string $name): Route
    {
        $route = $this->findByName($name);
        
        if (!$route) {
            throw new RouteNotFoundException("Route with name '{$name}' not found");
        }
        
        return $route;
    }
    
    /**
     * Check if named route exists
     */
    public function hasNamed(string $name): bool
    {
        return isset($this->namedRoutes[$name]);
    }
    
    /**
     * Count total routes
     */
    public function count(): int
    {
        return count($this->routes);
    }
    
    /**
     * Clear all routes
     */
    public function clear(): void
    {
        $this->routes = [];
        $this->namedRoutes = [];
    }
}
