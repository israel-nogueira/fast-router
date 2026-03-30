<?php

declare(strict_types=1);

namespace IsraelNogueira\FastRouter;

use Closure;
use IsraelNogueira\FastRouter\Exceptions\RouterException;

/**
 * Fast Router - Modern PHP Router with static and instance modes
 * 
 * @package IsraelNogueira\FastRouter
 * @author Israel Nogueira <israel@feats.com>
 * @license GPL-3.0-or-later
 * @link https://github.com/israel-nogueira/fast-router
 * 
 * @method static void get(array|string $config, Closure|array|string $callback = null)
 * @method static void post(array|string $config, Closure|array|string $callback = null)
 * @method static void put(array|string $config, Closure|array|string $callback = null)
 * @method static void delete(array|string $config, Closure|array|string $callback = null)
 * @method static void patch(array|string $config, Closure|array|string $callback = null)
 * @method static void options(array|string $config, Closure|array|string $callback = null)
 * @method static void any(array|string $config, Closure|array|string $callback = null)
 */
class Router
{
    /**
     * Static instance for static calls
     */
    private static ?Router $staticInstance = null;
    
    /**
     * Route collection
     */
    private RouteCollection $routes;
    
    /**
     * Current group stack
     * 
     * @var array<RouteGroup>
     */
    private array $groupStack = [];
    
    /**
     * Executed flag to prevent double execution
     */
    private bool $executed = false;
    
    /**
     * Debug mode
     */
    private bool $debug = false;
    
    public function __construct()
    {
        $this->routes = new RouteCollection();
    }
    
    /**
     * Enable debug mode
     */
    public function debug(bool $enabled = true): self
    {
        $this->debug = $enabled;
        return $this;
    }
    
    /**
     * Add route with specific HTTP method(s)
     * 
     * @param string|array<string> $methods
     * @param string|array<string, mixed> $config
     * @param Closure|array<mixed>|string|null $callback
     */
    public function addRoute(
        string|array $methods,
        string|array $config,
        Closure|array|string|null $callback = null
    ): self {
        // Normalize methods
        $methods = is_array($methods) ? $methods : [$methods];
        $methods = array_map('strtoupper', $methods);
        
        // Parse config
        if (is_string($config)) {
            $pattern = $config;
            $middlewares = [];
            $name = '';
            $continue = false;
        } else {
            $pattern = $config['prefix'] ?? $config['path'] ?? '';
            $middlewares = $config['middleware'] ?? [];
            $name = $config['name'] ?? '';
            $continue = $config['continue'] ?? false;
            $callback = $callback ?? ($config['callback'] ?? null);
        }
        
        // Apply group attributes
        $currentGroup = $this->getCurrentGroup();
        if ($currentGroup) {
            $pattern = $currentGroup->applyToPattern($pattern);
            $middlewares = array_merge($currentGroup->middlewares, $middlewares);
        }
        
        // Create and add route
        $route = new Route(
            methods: $methods,
            pattern: $pattern,
            callback: $callback,
            middlewares: $middlewares,
            name: $name,
            continue: $continue
        );
        
        $this->routes->add($route);
        
        return $this;
    }
    
    /**
     * Create route group
     * 
     * @param array<string, mixed>|string $config
     * @param Closure $callback
     */
    public function group(array|string $config, Closure $callback): self
    {
        // Parse config
        if (is_string($config)) {
            $prefix = $config;
            $middlewares = [];
            $namespace = '';
        } else {
            $prefix = $config['prefix'] ?? '';
            $middlewares = $config['middleware'] ?? [];
            $namespace = $config['namespace'] ?? '';
        }
        
        // Create group
        $group = new RouteGroup(
            prefix: $prefix,
            middlewares: $middlewares,
            namespace: $namespace
        );
        
        // Merge with parent if exists
        $currentGroup = $this->getCurrentGroup();
        if ($currentGroup) {
            $group = $group->merge($currentGroup);
        }
        
        // Push to stack
        $this->groupStack[] = $group;
        
        // Execute callback
        $callback($this);
        
        // Pop from stack
        array_pop($this->groupStack);
        
        return $this;
    }
    
    /**
     * Get current group
     */
    private function getCurrentGroup(): ?RouteGroup
    {
        if (empty($this->groupStack)) {
            return null;
        }
        
        return $this->groupStack[count($this->groupStack) - 1];
    }
    
    /**
     * Process and execute routes
     */
    public function processa(?string $method = null, ?string $url = null): mixed
    {
        if ($this->executed) {
            return null;
        }
        
        $this->executed = true;
        
        $method = $method ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $url = $url ?? RouteParser::getCurrentPath();
        
        if ($this->debug) {
            echo "<!-- Fast Router Debug -->\n";
            echo "<!-- Method: {$method} -->\n";
            echo "<!-- URL: {$url} -->\n";
            echo "<!-- Routes: {$this->routes->count()} -->\n";
        }
        
        foreach ($this->routes->all() as $route) {
            // Check method
            if (!$route->matchesMethod($method)) {
                continue;
            }
            
            // Check pattern
            $match = $route->match($url);
            
            if (!$match['status']) {
                continue;
            }
            
            if ($this->debug) {
                echo "<!-- Matched: {$route->pattern} -->\n";
            }
            
            // Execute route
            $result = $route->execute($match['params']);
            
            // Stop if route doesn't have continue flag
            if (!$route->continue) {
                return $result;
            }
        }
        
        // No route matched
        if ($this->debug) {
            echo "<!-- No route matched -->\n";
        }
        
        return null;
    }
    
    /**
     * Alias for processa()
     */
    public function dispatch(?string $method = null, ?string $url = null): mixed
    {
        return $this->processa($method, $url);
    }
    
    /**
     * Magic method to handle dynamic route methods
     * 
     * @param array<mixed> $arguments
     */
    public function __call(string $name, array $arguments): self
    {
        if ($name === 'group') {
            return $this->group(...$arguments);
        }
        
        // Handle HTTP method shortcuts
        $methods = match(strtoupper($name)) {
            'GET' => ['GET'],
            'POST' => ['POST'],
            'PUT' => ['PUT'],
            'DELETE' => ['DELETE'],
            'PATCH' => ['PATCH'],
            'OPTIONS' => ['OPTIONS'],
            'HEAD' => ['HEAD'],
            'ANY' => ['ANY'],
            'MATCH' => $arguments[0] ?? ['GET'], // First arg is methods array
            default => [strtoupper($name)]
        };
        
        // For MATCH, remove first argument (methods array)
        if (strtoupper($name) === 'MATCH') {
            array_shift($arguments);
        }
        
        return $this->addRoute($methods, ...$arguments);
    }
    
    /**
     * Get route collection
     */
    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }
    
    /**
     * Clear all routes and reset state
     */
    public function clear(): self
    {
        $this->routes->clear();
        $this->groupStack = [];
        $this->executed = false;
        return $this;
    }

	/**
	 * Scan a directory for module router files and register routes
	 * Each module must have a {ModuleName}.router.php returning a callable
	 * 
	 * @param string $dir Absolute path to modules directory
	 */
	public function map(string $dir): self
	{
		if (!is_dir($dir)) return $this;

		foreach (glob("{$dir}/*/") as $modulePath) {
			$moduleName = basename($modulePath);
			$routerFile = $modulePath . ucfirst($moduleName) . '.router.php';

			if (file_exists($routerFile)) {
				$fn = require $routerFile;
				if (is_callable($fn)) {
					$fn($this);
				}
			}
		}
		return $this;
	}


    // ==================== STATIC MODE ====================
    
    /**
     * Get static instance
     */
    private static function getInstance(): Router
    {
        if (self::$staticInstance === null) {
            self::$staticInstance = new self();
        }
        
        return self::$staticInstance;
    }
    
    /**
     * Static magic method
     * 
     * @param array<mixed> $arguments
     */
    public static function __callStatic(string $name, array $arguments): mixed
    {
        $instance = self::getInstance();
        
        // Call instance method and return result
        return $instance->{$name}(...$arguments);
    }
    
    /**
     * Reset static instance
     */
    public static function resetStatic(): void
    {
        self::$staticInstance = null;
    }









	
}
