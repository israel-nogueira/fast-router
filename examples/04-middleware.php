<?php

/**
 * Middleware Example
 */

require_once __DIR__ . '/../vendor/autoload.php';

use IsraelNogueira\FastRouter\Router;

// ============================================
// MIDDLEWARE CLASSES
// ============================================

class AuthMiddleware
{
    public function handle(array $returns, callable $next): mixed
    {
        // Check authentication
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo "Unauthorized";
            return null;
        }
        
        // Add data to returns
        $returns[] = [
            'middleware' => 'auth',
            'user' => $_SESSION['user']
        ];
        
        // Continue to next middleware
        return $next($returns);
    }
}

class LogMiddleware
{
    public function handle(array $returns, callable $next): mixed
    {
        // Log request
        error_log("Request: " . $_SERVER['REQUEST_URI']);
        
        // Continue
        return $next($returns);
    }
}

// ============================================
// USING MIDDLEWARE IN ROUTES
// ============================================

Router::get([
    'prefix' => 'admin/dashboard',
    'middleware' => [AuthMiddleware::class, LogMiddleware::class]
], function() {
    echo "Admin Dashboard";
});

// ============================================
// USING MIDDLEWARE IN GROUPS
// ============================================

Router::group([
    'prefix' => 'admin',
    'middleware' => [AuthMiddleware::class]
], function($router) {
    
    $router->get('dashboard', function() {
        echo "Admin Dashboard";
    });
    
    $router->get([
        'prefix' => 'users',
        'middleware' => [LogMiddleware::class]
    ], function() {
        echo "Admin Users (with extra log middleware)";
    });
});

// ============================================
// MIDDLEWARE WITH @method SYNTAX
// ============================================

Router::get([
    'prefix' => 'profile',
    'middleware' => ['AuthMiddleware@handle']
], function() {
    echo "User Profile";
});

// ============================================
// STOPPING MIDDLEWARE CHAIN
// ============================================

class AdminCheckMiddleware
{
    public function handle(array $returns, callable $next): mixed
    {
        if (!$this->isAdmin()) {
            http_response_code(403);
            die("Forbidden");
        }
        
        return $next($returns);
    }
    
    private function isAdmin(): bool
    {
        return ($_SESSION['role'] ?? '') === 'admin';
    }
}
