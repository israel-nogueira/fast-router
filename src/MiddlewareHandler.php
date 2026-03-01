<?php

declare(strict_types=1);

namespace IsraelNogueira\FastRouter;

use Closure;
use IsraelNogueira\FastRouter\Exceptions\MiddlewareException;

/**
 * Middleware Handler - Execute middleware chain
 * 
 * @package IsraelNogueira\FastRouter
 * @author Israel Nogueira <israel@feats.com>
 * @license GPL-3.0-or-later
 */
class MiddlewareHandler
{
    /**
     * Loaded middleware instances cache
     * 
     * @var array<string, object>
     */
    private static array $instances = [];
    
    /**
     * Execute middleware chain
     * 
     * @param array<string> $middlewares
     * @param Closure $finalCallback
     * @return mixed
     */
    public static function execute(array $middlewares, Closure $finalCallback): mixed
    {
        if (empty($middlewares)) {
            return $finalCallback([]);
        }
        
        $returns = [];
        $index = 0;
        
        $next = function(array $return = []) use (&$next, &$index, $middlewares, $finalCallback, &$returns): mixed {
            $returns = array_merge($returns, $return);
            
            if ($index >= count($middlewares)) {
                return $finalCallback($returns);
            }
            
            $middleware = $middlewares[$index++];
            
            return self::call($middleware, $returns, $next);
        };
        
        return $next([]);
    }
    
    /**
     * Call a single middleware
     * 
     * @param string $middleware
     * @param array<mixed> $returns
     * @param Closure $next
     * @return mixed
     */
    private static function call(string $middleware, array $returns, Closure $next): mixed
    {
        // Parse middleware format: Class@method or just Class
        if (strpos($middleware, '@') !== false) {
            [$class, $method] = explode('@', $middleware, 2);
        } else {
            $class = $middleware;
            $method = 'handle';
        }
        
        // Load class if needed
        if (!class_exists($class)) {
            self::loadMiddlewareFile($class);
        }
        
        if (!class_exists($class)) {
            throw new MiddlewareException("Middleware class not found: {$class}");
        }
        
        // Get or create instance
        $instance = self::$instances[$class] ?? new $class();
        self::$instances[$class] = $instance;
        
        if (!method_exists($instance, $method)) {
            throw new MiddlewareException("Method {$method} not found in middleware {$class}");
        }
        
        // Execute middleware
        return $instance->{$method}($returns, $next);
    }
    
    /**
     * Try to autoload middleware file
     */
    private static function loadMiddlewareFile(string $class): void
    {
        // Try composer autoload first
        $autoloadFiles = [
            __DIR__ . '/../../../../autoload.php',
            __DIR__ . '/../../../autoload.php',
            __DIR__ . '/../../autoload.php',
            __DIR__ . '/../autoload.php',
        ];
        
        foreach ($autoloadFiles as $file) {
            if (file_exists($file)) {
                require_once $file;
                if (class_exists($class)) {
                    return;
                }
            }
        }
        
        // Fallback to manual path detection
        $basePaths = [
            getcwd(),
            realpath(__DIR__ . '/../../../../'),
            realpath(__DIR__ . '/../../../'),
        ];
        
        foreach ($basePaths as $basePath) {
            if (!$basePath) {
                continue;
            }
            
            $classPath = str_replace('\\', DIRECTORY_SEPARATOR, $class);
            
            $patterns = [
                $basePath . DIRECTORY_SEPARATOR . $classPath . '.php',
                $basePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $classPath . '.php',
                $basePath . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $classPath . '.php',
            ];
            
            foreach ($patterns as $file) {
                if (file_exists($file)) {
                    require_once $file;
                    return;
                }
            }
        }
    }
    
    /**
     * Clear middleware instances cache
     */
    public static function clearCache(): void
    {
        self::$instances = [];
    }
}
