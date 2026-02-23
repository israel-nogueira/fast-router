<?php

declare(strict_types=1);

namespace IsraelNogueira\FastRouter;

/**
 * Route Parser - Handle regex patterns and parameter extraction
 * 
 * @package IsraelNogueira\FastRouter
 * @author Israel Nogueira <israel@feats.com>
 * @license GPL-3.0-or-later
 */
class RouteParser
{
    /**
     * Parse route pattern to regex
     */
    public static function parseToRegex(string $pattern): string
    {
        // Remove trailing slashes
        $pattern = trim($pattern, '/');
        
        // Escape special regex characters except our placeholders
        $pattern = preg_replace_callback('/[.\/\[\](){}^$|*+?\\\\]/', function($matches) {
            return '\\' . $matches[0];
        }, $pattern);
        
        // Handle optional wildcard [/*/] - matches /anything or empty
        $pattern = preg_replace('/\\\\\[\\\\\\/\\\\\*\\\\\\/\\\\\]/', '(?:/.*)?', $pattern);
        
        // Handle optional parameters [/{param}/]
        $pattern = preg_replace_callback('/\\\\\[\/\{([^}]+)\}\/\\\\\]/', function($matches) {
            return '(?:/(' . self::getRegexForParam($matches[1]) . '))?';
        }, $pattern);
        
        // Handle required parameters {param} or {param:regex}
        $pattern = preg_replace_callback('/\\\\\{([^}]+)\\\\\}/', function($matches) {
            return '(' . self::getRegexForParam($matches[1]) . ')';
        }, $pattern);
        
        return '#^' . $pattern . '$#';
    }
    
    /**
     * Extract regex pattern from parameter definition
     */
    private static function getRegexForParam(string $param): string
    {
        // Check if has custom regex: {id:\d+}
        if (strpos($param, ':') !== false) {
            [, $regex] = explode(':', $param, 2);
            return $regex;
        }
        
        // Default: any non-slash character
        return '[^/]+';
    }
    
    /**
     * Extract parameter names from pattern
     * 
     * @return array<string>
     */
    public static function extractParamNames(string $pattern): array
    {
        $names = [];
        
        // Match both {param} and {param:regex}
        if (preg_match_all('/\{([^}:]+)(?::[^}]+)?\}/', $pattern, $matches)) {
            $names = $matches[1];
        }
        
        return $names;
    }
    
    /**
     * Match URL against pattern and extract parameters
     * 
     * @return array{status: bool, params: array<string, mixed>, regex: string}
     */
    public static function match(string $pattern, string $url): array
    {
        $regex = self::parseToRegex($pattern);
        $url = trim($url, '/');
        
        $result = [
            'status' => false,
            'params' => [],
            'regex' => $regex
        ];
        
        if (preg_match($regex, $url, $matches)) {
            array_shift($matches); // Remove full match
            
            $paramNames = self::extractParamNames($pattern);
            
            foreach ($paramNames as $index => $name) {
                $result['params'][$name] = $matches[$index] ?? null;
            }
            
            $result['status'] = true;
        }
        
        return $result;
    }
    
    /**
     * Get current URL path from request
     */
    public static function getCurrentPath(): string
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Remove query string
        if (($pos = strpos($path, '?')) !== false) {
            $path = substr($path, 0, $pos);
        }
        
        return $path;
    }
    
    /**
     * Normalize path by removing duplicate slashes
     */
    public static function normalizePath(string $path): string
    {
        $path = preg_replace('#/+#', '/', $path);
        return '/' . trim($path, '/');
    }
}
