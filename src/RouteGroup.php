<?php

declare(strict_types=1);

namespace IsraelNogueira\FastRouter;

use Closure;

/**
 * Route Group - Handle route grouping with prefix and middleware
 * 
 * @package IsraelNogueira\FastRouter
 * @author Israel Nogueira <israel@feats.com>
 * @license GPL-3.0-or-later
 */
class RouteGroup
{
    /**
     * @param string $prefix Group prefix
     * @param array<string> $middlewares Group middlewares
     * @param string $namespace Group namespace
     */
    public function __construct(
        public readonly string $prefix = '',
        public readonly array $middlewares = [],
        public readonly string $namespace = ''
    ) {
    }
    
    /**
     * Merge with parent group
     */
    public function merge(RouteGroup $parent): self
    {
        return new self(
            prefix: $this->mergePrefix($parent->prefix, $this->prefix),
            middlewares: array_merge($parent->middlewares, $this->middlewares),
            namespace: $this->namespace ?: $parent->namespace
        );
    }
    
    /**
     * Merge prefixes
     */
    private function mergePrefix(string $parent, string $current): string
    {
        if ($parent === '' && $current === '') {
            return '';
        }
        
        if ($parent === '') {
            return '/' . trim($current, '/');
        }
        
        if ($current === '') {
            return '/' . trim($parent, '/');
        }
        
        return '/' . trim($parent, '/') . '/' . trim($current, '/');
    }
    
    /**
     * Apply group attributes to route pattern
     */
    public function applyToPattern(string $pattern): string
    {
        if ($this->prefix === '') {
            return $pattern;
        }
        
        return rtrim($this->prefix, '/') . '/' . ltrim($pattern, '/');
    }
    
    /**
     * Check if group prefix matches current URL
     */
    public function matches(string $url): bool
    {
        if ($this->prefix === '') {
            return true;
        }
        
        $prefix = '/' . trim($this->prefix, '/');
        $url = '/' . trim($url, '/');
        
        return strpos($url, $prefix) === 0;
    }
}
