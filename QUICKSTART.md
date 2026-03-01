# ⚡ Quick Start Guide

Get started with Fast Router in 5 minutes!

## 🚀 Installation

### Via Composer (Recommended)

```bash
composer require israel-nogueira/fast-router
```

### Manual Installation

1. Download the [latest release](https://github.com/israel-nogueira/fast-router/releases)
2. Extract to your project
3. Include the autoloader:

```php
require_once 'path/to/fast-router/src/Router.php';
// ... include other files manually
```

---

## 📝 Your First Route

Create an `index.php` file:

```php
<?php

require 'vendor/autoload.php';

use IsraelNogueira\FastRouter\Router;

Router::get('/', function() {
    echo "Hello World!";
});
```

### Test It

#### Using PHP Built-in Server

```bash
php -S localhost:8000 index.php
```

Visit: http://localhost:8000

#### Using Apache/Nginx

Make sure you have URL rewriting enabled (see configuration below).

---

## 🔧 Web Server Configuration

### Apache (.htaccess)

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    # If file/directory doesn't exist, route through index.php
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>
```

### Nginx

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

---

## 📚 Common Patterns

### 1. Routes with Parameters

```php
<?php

use IsraelNogueira\FastRouter\Router;

Router::get('user/{id}', function($id) {
    echo "User ID: {$id}";
});

// URL: /user/123
// Output: User ID: 123
```

### 2. Using Controllers

```php
<?php

use IsraelNogueira\FastRouter\Router;

class UserController
{
    public function show($id)
    {
        echo "User: {$id}";
    }
}

Router::get('user/{id}', [UserController::class, 'show']);
```

### 3. Route Groups

```php
<?php

use IsraelNogueira\FastRouter\Router;

Router::group('admin', function($router) {
    
    $router->get('dashboard', function() {
        echo "Admin Dashboard";
    });
    
    $router->get('users', function() {
        echo "Admin Users";
    });
});

// URLs:
// /admin/dashboard → Admin Dashboard
// /admin/users → Admin Users
```

### 4. POST Routes

```php
<?php

use IsraelNogueira\FastRouter\Router;

Router::get('contact', function() {
    // Show form
    echo '<form method="POST" action="/contact">
            <input name="name">
            <button>Send</button>
          </form>';
});

Router::post('contact', function() {
    // Process form
    $name = $_POST['name'] ?? '';
    echo "Thanks, {$name}!";
});
```

### 5. JSON API

```php
<?php

use IsraelNogueira\FastRouter\Router;

Router::get('api/users', function() {
    header('Content-Type: application/json');
    echo json_encode([
        'users' => [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
        ]
    ]);
});
```

---

## 🛡️ Authentication Example

```php
<?php

use IsraelNogueira\FastRouter\Router;

// Middleware
class AuthMiddleware
{
    public function handle(array $returns, callable $next): mixed
    {
        session_start();
        
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            die('Unauthorized');
        }
        
        return $next($returns);
    }
}

// Protected routes
Router::group([
    'prefix' => 'admin',
    'middleware' => [AuthMiddleware::class]
], function($router) {
    
    $router->get('dashboard', function() {
        echo "Secure Dashboard";
    });
});
```

---

## 📂 Recommended Project Structure

```
your-project/
├── public/
│   └── index.php          # Entry point
├── app/
│   ├── Controllers/
│   │   ├── HomeController.php
│   │   └── UserController.php
│   ├── Middleware/
│   │   └── AuthMiddleware.php
│   └── routes.php         # Route definitions
├── vendor/                # Composer dependencies
└── composer.json
```

### public/index.php

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load routes
require_once __DIR__ . '/../app/routes.php';
```

### app/routes.php

```php
<?php

use IsraelNogueira\FastRouter\Router;
use App\Controllers\HomeController;

$router = new Router();

$router->get('/', [HomeController::class, 'index']);
$router->get('about', [HomeController::class, 'about']);

// ... more routes

$router->dispatch();
```

---

## 🎨 Complete Example

```php
<?php

require 'vendor/autoload.php';

use IsraelNogueira\FastRouter\Router;

$router = new Router();

// Home
$router->get('/', function() {
    echo '<h1>Welcome</h1>';
});

// About
$router->get('about', function() {
    echo '<h1>About Us</h1>';
});

// User profile
$router->get('user/{id:\d+}', function($id) {
    echo "<h1>User Profile</h1>";
    echo "<p>ID: {$id}</p>";
});

// Blog post with optional slug
$router->get('blog/{year:\d{4}}/{month:\d{2}}[/{slug}/]', 
    function($year, $month, $slug = null) {
        echo "<h1>Blog Archive</h1>";
        echo "<p>Date: {$year}-{$month}</p>";
        if ($slug) echo "<p>Post: {$slug}</p>";
    }
);

// API
$router->group('api', function($router) {
    
    $router->get('users', function() {
        header('Content-Type: application/json');
        echo json_encode(['users' => []]);
    });
    
    $router->get('users/{id}', function($id) {
        header('Content-Type: application/json');
        echo json_encode(['user' => ['id' => $id]]);
    });
});

// 404
$router->any('.*', function() {
    http_response_code(404);
    echo '<h1>404 Not Found</h1>';
});

// Run
$router->dispatch();
```

---

## 🐛 Debugging

Enable debug mode during development:

```php
$router = new Router();
$router->debug(true);

// Routes...

$router->dispatch();
```

This will output HTML comments with route matching information.

---

## ✅ Next Steps

1. ✨ Read the [complete documentation](README.md)
2. 🔍 Check out [examples](examples/)
3. 🛠️ Learn about [middleware](README.md#middleware)
4. 🎯 Explore [advanced features](README.md#advanced-features)
5. 🤝 [Contribute](CONTRIBUTING.md) to the project

---

## 💡 Tips

### Performance
- Group related routes together
- Use regex constraints when possible
- Avoid too many dynamic parameters

### Security
- Always validate user input
- Use middleware for authentication
- Sanitize parameters before database queries

### Organization
- Separate routes into different files
- Use controllers for complex logic
- Keep route definitions clean and simple

---

## 🆘 Common Issues

### Routes not working?

1. ✅ Check web server configuration (URL rewriting)
2. ✅ Verify file permissions
3. ✅ Enable debug mode
4. ✅ Check for typos in route patterns

### Parameters not passed?

1. ✅ Verify regex constraints
2. ✅ Check parameter names match
3. ✅ Use debug mode to see regex

### 404 on everything?

1. ✅ URL rewriting not configured
2. ✅ Index file in wrong location
3. ✅ Apache mod_rewrite not enabled

---

## 📬 Need Help?

- 📖 [Full Documentation](README.md)
- 💬 [GitHub Issues](https://github.com/israel-nogueira/fast-router/issues)
- 📧 Email: contato@israelnogueira.com

---

<p align="center">
  <strong>Happy Routing! 🚀</strong>
</p>
