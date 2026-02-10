<?php

/**
 * Controllers Example
 */

require_once __DIR__ . '/../vendor/autoload.php';

use IsraelNogueira\FastRouter\Router;

// ============================================
// CONTROLLER CLASSES
// ============================================

class UserController
{
    public function index()
    {
        echo "User List";
    }
    
    public function show($id)
    {
        echo "User ID: {$id}";
    }
    
    public function store()
    {
        echo "Create User";
    }
    
    public function update($id)
    {
        echo "Update User ID: {$id}";
    }
    
    public function destroy($id)
    {
        echo "Delete User ID: {$id}";
    }
}

// ============================================
// USING CONTROLLERS - STRING SYNTAX
// ============================================

Router::get('users', 'UserController@index');
Router::get('users/{id}', 'UserController@show');
Router::post('users', 'UserController@store');
Router::put('users/{id}', 'UserController@update');
Router::delete('users/{id}', 'UserController@destroy');

// ============================================
// USING CONTROLLERS - ARRAY SYNTAX
// ============================================

Router::get('products', [ProductController::class, 'index']);
Router::get('products/{id}', [ProductController::class, 'show']);

// ============================================
// MULTIPLE CALLBACKS
// ============================================

Router::get('dashboard', [
    'logRequest',  // Function name
    'UserController@checkAuth',
    [DashboardController::class, 'index'],
    function() {
        // Additional logic
        echo " - Rendered at " . date('H:i:s');
    }
]);

// ============================================
// RESTFUL RESOURCE
// ============================================

$router = new Router();

// All CRUD routes for a resource
$resource = 'posts';
$controller = PostController::class;

$router->get($resource, [$controller, 'index']);          // GET /posts
$router->get("{$resource}/create", [$controller, 'create']); // GET /posts/create
$router->post($resource, [$controller, 'store']);         // POST /posts
$router->get("{$resource}/{id}", [$controller, 'show']);  // GET /posts/123
$router->get("{$resource}/{id}/edit", [$controller, 'edit']); // GET /posts/123/edit
$router->put("{$resource}/{id}", [$controller, 'update']); // PUT /posts/123
$router->delete("{$resource}/{id}", [$controller, 'destroy']); // DELETE /posts/123

$router->dispatch();
