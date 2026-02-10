# 🚀 Fast Router

[![Latest Version](https://poser.pugx.org/israel-nogueira/fast-router/v/stable.svg)](https://packagist.org/packages/israel-nogueira/fast-router)
[![Total Downloads](https://poser.pugx.org/israel-nogueira/fast-router/downloads)](https://packagist.org/packages/israel-nogueira/fast-router)
[![License](https://poser.pugx.org/israel-nogueira/fast-router/license.svg)](https://packagist.org/packages/israel-nogueira/fast-router)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-8892BF.svg)](https://php.net/)

Modern, fast and flexible PHP router with support for **static** and **instance** modes, middleware chains, route groups, and advanced routing features.

---

## ✨ Features

- ✅ **Dual Mode**: Static (auto-dispatch) and Instance (manual control)
- ✅ **Route Parameters**: Simple `{id}` and regex `{id:\d+}` with optional parameters
- ✅ **Route Groups**: Nested groups with prefix and middleware inheritance
- ✅ **Middleware Chain**: Elegant middleware system with dependency injection
- ✅ **Multiple HTTP Methods**: GET, POST, PUT, DELETE, PATCH, OPTIONS, ANY, and custom
- ✅ **Controller Support**: String syntax, array syntax, and closures
- ✅ **Named Routes**: Reference routes by name (coming soon)
- ✅ **PSR-4 Autoloading**: Modern namespace structure
- ✅ **Type Safety**: Full PHP 8.1+ type hints
- ✅ **Zero Dependencies**: Standalone library
- ✅ **Debug Mode**: Built-in debugging capabilities

---

## 📦 Installation

```bash
composer require israel-nogueira/fast-router
```

### Requirements

- PHP >= 8.1
- Composer

---

## 🚀 Quick Start

### Basic Usage

```php
<?php

require 'vendor/autoload.php';

use IsraelNogueira\FastRouter\Router;

// Simple route
Router::get('/', function() {
    echo "Hello World!";
});

// Route with parameter
Router::get('user/{id}', function($id) {
    echo "User ID: {$id}";
});

// POST route
Router::post('contact', function() {
    echo "Form submitted!";
});
```

---

## 📚 Documentation

### Table of Contents

- [Static vs Instance Mode](#static-vs-instance-mode)
- [Route Parameters](#route-parameters)
- [Route Groups](#route-groups)
- [Middleware](#middleware)
- [Controllers](#controllers)
- [HTTP Methods](#http-methods)
- [Advanced Features](#advanced-features)

---

## 🔄 Static vs Instance Mode

### Static Mode (Auto-Dispatch)

Routes are automatically dispatched after each definition:

```php
use IsraelNogueira\FastRouter\Router;

Router::get('/', function() {
    echo "Home";
});

Router::get('about', function() {
    echo "About";
});

// No need to call dispatch() - routes auto-execute
```

### Instance Mode (Manual Control)

For more control, create a Router instance:

```php
$router = new Router();

$router->get('/', function() {
    echo "Home";
});

$router->get('about', function() {
    echo "About";
});

// Manually dispatch routes
$router->dispatch();
```

**When to use each:**
- **Static Mode**: Simple applications, rapid prototyping
- **Instance Mode**: Testing, multiple routers, advanced control

---

## 🎯 Route Parameters

### Simple Parameters

```php
Router::get('user/{id}', function($id) {
    echo "User: {$id}";
});

Router::get('post/{year}/{month}/{slug}', function($year, $month, $slug) {
    echo "Post: {$slug} from {$month}/{$year}";
});
```

### Regex Constraints

```php
// Only numbers
Router::get('product/{id:\d+}', function($id) {
    echo "Product: {$id}";
});

// Only lowercase letters and hyphens
Router::get('category/{slug:[a-z-]+}', function($slug) {
    echo "Category: {$slug}";
});

// Date pattern
Router::get('archive/{date:\d{4}-\d{2}-\d{2}}', function($date) {
    echo "Archive: {$date}";
});
```

### Optional Parameters

```php
// Single optional parameter
Router::get('blog/{id:\d+}[/{title}/]', function($id, $title = null) {
    echo "Blog: {$id}";
    if ($title) echo " - {$title}";
});

// Multiple optional parameters
Router::get('archive/{year:\d{4}}[/{month:\d{2}}[/{day:\d{2}}/]/]', 
    function($year, $month = null, $day = null) {
        echo "Archive: {$year}/{$month}/{$day}";
    }
);
```

**Syntax Rules:**
- Required: `{param}` or `{param:regex}`
- Optional: `[/{param}/]` with slashes inside brackets
- Nested: `[/{a}[/{b}/]/]` for multiple optional params

---

## 📂 Route Groups

### Simple Groups

```php
Router::group('admin', function($router) {
    
    $router->get('dashboard', function() {
        echo "Admin Dashboard";
        // URL: /admin/dashboard
    });
    
    $router->get('users', function() {
        echo "Admin Users";
        // URL: /admin/users
    });
});
```

### Nested Groups

```php
Router::group('admin', function($router) {
    
    $router->group('products', function($router) {
        
        $router->get('list', function() {
            echo "Product List";
            // URL: /admin/products/list
        });
        
        $router->group('category', function($router) {
            
            $router->get('list', function() {
                echo "Category List";
                // URL: /admin/products/category/list
            });
        });
    });
});
```

### Groups with Middleware

```php
Router::group([
    'prefix' => 'admin',
    'middleware' => ['auth', 'admin']
], function($router) {
    
    $router->get('dashboard', function() {
        echo "Secure Dashboard";
    });
    
    $router->get('settings', function() {
        echo "Secure Settings";
    });
});
```

---

## 🛡️ Middleware

Middleware allows you to filter HTTP requests before they reach your route handler.

### Creating Middleware

```php
class AuthMiddleware
{
    public function handle(array $returns, callable $next): mixed
    {
        // Check authentication
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            die('Unauthorized');
        }
        
        // Add data to returns array
        $returns[] = [
            'middleware' => 'auth',
            'user' => $_SESSION['user']
        ];
        
        // Continue to next middleware
        return $next($returns);
    }
}
```

### Using Middleware

#### On Single Routes

```php
Router::get([
    'prefix' => 'dashboard',
    'middleware' => [AuthMiddleware::class]
], function() {
    echo "Protected Dashboard";
});
```

#### On Route Groups

```php
Router::group([
    'prefix' => 'admin',
    'middleware' => [AuthMiddleware::class, LogMiddleware::class]
], function($router) {
    
    $router->get('users', function() {
        echo "Admin Users";
    });
    
    $router->get('settings', function() {
        echo "Admin Settings";
    });
});
```

#### String Syntax

```php
Router::get([
    'prefix' => 'profile',
    'middleware' => ['App\Middleware\Auth@handle']
], function() {
    echo "User Profile";
});
```

### Middleware Chain

Middlewares execute in order. Each middleware must call `$next($returns)` to continue:

```php
class FirstMiddleware
{
    public function handle(array $returns, callable $next): mixed
    {
        echo "Before First\n";
        $result = $next($returns);
        echo "After First\n";
        return $result;
    }
}

class SecondMiddleware
{
    public function handle(array $returns, callable $next): mixed
    {
        echo "Before Second\n";
        $result = $next($returns);
        echo "After Second\n";
        return $result;
    }
}

// Output:
// Before First
// Before Second
// [Route Handler]
// After Second
// After First
```

### Stopping the Chain

To stop execution, don't call `$next()`:

```php
class AdminCheckMiddleware
{
    public function handle(array $returns, callable $next): mixed
    {
        if (!$this->isAdmin()) {
            http_response_code(403);
            die('Forbidden');
            // Chain stops here
        }
        
        return $next($returns);
    }
}
```

---

## 🎮 Controllers

### Controller Class

```php
class UserController
{
    public function index()
    {
        echo "User List";
    }
    
    public function show($id)
    {
        echo "User: {$id}";
    }
    
    public function store()
    {
        echo "Create User";
    }
}
```

### String Syntax

```php
Router::get('users', 'UserController@index');
Router::get('users/{id}', 'UserController@show');
Router::post('users', 'UserController@store');
```

### Array Syntax

```php
Router::get('users', [UserController::class, 'index']);
Router::get('users/{id}', [UserController::class, 'show']);
Router::post('users', [UserController::class, 'store']);
```

### Multiple Callbacks

Execute multiple callbacks in sequence:

```php
Router::get('dashboard', [
    'logRequest',  // Function
    'UserController@auth',  // Controller method
    [DashboardController::class, 'index'],  // Array syntax
    function() {  // Closure
        echo " - Rendered";
    }
]);
```

### RESTful Resource Routes

```php
$resource = 'posts';
$controller = PostController::class;

Router::get($resource, [$controller, 'index']);           // GET /posts
Router::get("{$resource}/create", [$controller, 'create']); // GET /posts/create
Router::post($resource, [$controller, 'store']);          // POST /posts
Router::get("{$resource}/{id}", [$controller, 'show']);   // GET /posts/123
Router::get("{$resource}/{id}/edit", [$controller, 'edit']); // GET /posts/123/edit
Router::put("{$resource}/{id}", [$controller, 'update']); // PUT /posts/123
Router::delete("{$resource}/{id}", [$controller, 'destroy']); // DELETE /posts/123
```

---

## 🌐 HTTP Methods

### Standard Methods

```php
Router::get('/', fn() => 'GET');
Router::post('/', fn() => 'POST');
Router::put('/', fn() => 'PUT');
Router::delete('/', fn() => 'DELETE');
Router::patch('/', fn() => 'PATCH');
Router::options('/', fn() => 'OPTIONS');
Router::head('/', fn() => 'HEAD');
```

### Any Method

Accepts all HTTP methods:

```php
Router::any('search', function() {
    echo "Works with any HTTP method";
});
```

### Multiple Specific Methods

```php
// Using math() method
Router::math(['GET', 'POST'], 'form', function() {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo "Show form";
    } else {
        echo "Process form";
    }
});
```

### Custom Methods

```php
Router::addRoute(['CUSTOM', 'SPECIAL'], 'endpoint', function() {
    echo "Custom HTTP method";
});
```

---

## 🔧 Advanced Features

### Continue to Next Route

By default, router stops after first match. To continue:

```php
Router::get([
    'prefix' => 'log/{id}',
    'continue' => true
], function($id) {
    error_log("Logging access to: {$id}");
});

Router::get('log/{id}', function($id) {
    echo "Viewing log: {$id}";
});
```

### Debug Mode

Enable debugging to see route matching process:

```php
$router = new Router();
$router->debug(true);

$router->get('test', function() {
    echo "Test";
});

$router->dispatch();

// Output includes HTML comments:
// <!-- Fast Router Debug -->
// <!-- Method: GET -->
// <!-- URL: /test -->
// <!-- Routes: 1 -->
// <!-- Matched: test -->
```

### Custom Dispatch

Test routes with custom URL and method:

```php
$router = new Router();

$router->get('api/users/{id}', function($id) {
    return ['user_id' => $id];
});

// Dispatch with custom parameters
$result = $router->dispatch('GET', '/api/users/123');
```

### Route Collection

Access registered routes:

```php
$router = new Router();

$router->get('home', fn() => 'Home');
$router->get('about', fn() => 'About');

$collection = $router->getRoutes();
echo "Total routes: " . $collection->count();

foreach ($collection->all() as $route) {
    echo $route->pattern . "\n";
}
```

### Clear Routes

```php
$router = new Router();

$router->get('test1', fn() => 'Test 1');
$router->get('test2', fn() => 'Test 2');

// Clear all routes
$router->clear();
```

---

## 🧪 Testing

### Unit Testing Example

```php
use PHPUnit\Framework\TestCase;
use IsraelNogueira\FastRouter\Router;

class RouterTest extends TestCase
{
    public function testBasicRoute()
    {
        $router = new Router();
        
        $router->get('test', function() {
            return 'success';
        });
        
        $result = $router->dispatch('GET', '/test');
        
        $this->assertEquals('success', $result);
    }
    
    public function testRouteParameters()
    {
        $router = new Router();
        
        $router->get('user/{id:\d+}', function($id) {
            return $id;
        });
        
        $result = $router->dispatch('GET', '/user/123');
        
        $this->assertEquals('123', $result);
    }
}
```

---

## 📖 Migration from v1.x

### Breaking Changes

1. **Namespace change**: `IsraelNogueira\fastRouter` → `IsraelNogueira\FastRouter`
2. **PHP requirement**: Now requires PHP 8.1+
3. **Type hints**: All methods now use strict types

### Migration Steps

```php
// OLD (v1.x)
use IsraelNogueira\fastRouter\router;

router::get('/', function() {});

// NEW (v2.x)
use IsraelNogueira\FastRouter\Router;

Router::get('/', function() {});
```

**Good news**: All public APIs remain compatible! Just update the namespace.

---

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

### Development Setup

```bash
git clone https://github.com/israel-nogueira/fast-router.git
cd fast-router
composer install
```

### Running Tests

```bash
composer test
```

### Code Quality

```bash
composer analyse
```

---

## 📄 License

This project is licensed under the GPL-3.0-or-later License - see the [LICENSE](LICENSE) file for details.

---

## 👤 Author

**Israel Nogueira**

- Email: contato@israelnogueira.com
- GitHub: [@israel-nogueira](https://github.com/israel-nogueira)

---

## 🌟 Show Your Support

Give a ⭐️ if this project helped you!

---

## 📝 Changelog

### v2.0.0 (2026-02-10)

- ✨ Complete refactoring with modern PHP 8.1+ features
- ✨ Added strict type hints throughout
- ✨ Improved middleware system
- ✨ Better error handling with custom exceptions
- ✨ PSR-4 autoloading structure
- ✨ Debug mode
- 🐛 Fixed various edge cases in route matching
- 📚 Complete documentation rewrite

### v1.x

- Initial releases with basic routing functionality

---

<p align="center">Made with ❤️ by Israel Nogueira</p>
