<?php

/**
 * Basic Usage Example
 */

require_once __DIR__ . '/../vendor/autoload.php';

use IsraelNogueira\FastRouter\Router;

// ============================================
// STATIC MODE (auto-dispatch after each route)
// ============================================

Router::get('/', function() {
    echo "Home Page";
});

Router::get('about', function() {
    echo "About Page";
});

Router::post('contact', function() {
    echo "Contact Form Submitted";
});

Router::any('search', function() {
    echo "Search works with any HTTP method";
});

// Multiple HTTP methods
Router::math(['GET', 'POST'], 'api/data', function() {
    echo "API Data";
});

// ============================================
// INSTANCE MODE (manual dispatch)
// ============================================

$router = new Router();

$router->get('/', function() {
    echo "Home Page";
});

$router->post('contact', function() {
    echo "Contact Form";
});

// Process routes
$router->dispatch();
