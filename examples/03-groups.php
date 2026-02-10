<?php

/**
 * Route Groups Example
 */

require_once __DIR__ . '/../vendor/autoload.php';

use IsraelNogueira\FastRouter\Router;

// ============================================
// SIMPLE GROUPS
// ============================================

Router::group('admin', function($router) {
    $router->get('dashboard', function() {
        echo "Admin Dashboard";
    });
    
    $router->get('users', function() {
        echo "Admin Users";
    });
});

// ============================================
// NESTED GROUPS
// ============================================

Router::group('admin', function($router) {
    
    $router->get('dashboard', function() {
        echo "Admin Dashboard";
    });
    
    $router->group('products', function($router) {
        
        $router->get('list', function() {
            echo "Admin Products List";
            // URL: /admin/products/list
        });
        
        $router->get('create', function() {
            echo "Admin Products Create";
            // URL: /admin/products/create
        });
        
        $router->group('category', function($router) {
            
            $router->get('list', function() {
                echo "Admin Products Category List";
                // URL: /admin/products/category/list
            });
            
        });
    });
});

// ============================================
// GROUPS WITH ARRAY CONFIG
// ============================================

Router::group([
    'prefix' => 'api/v1',
    'middleware' => ['auth', 'api']
], function($router) {
    
    $router->get('users', function() {
        echo "API Users";
    });
    
    $router->post('users', function() {
        echo "Create User";
    });
});
