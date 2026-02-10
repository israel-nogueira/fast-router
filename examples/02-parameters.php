<?php

/**
 * Route Parameters Example
 */

require_once __DIR__ . '/../vendor/autoload.php';

use IsraelNogueira\FastRouter\Router;

// ============================================
// SIMPLE PARAMETERS
// ============================================

Router::get('user/{id}', function($id) {
    echo "User ID: {$id}";
});

Router::get('user/{id}/{name}', function($id, $name) {
    echo "User: {$name} (ID: {$id})";
});

// ============================================
// REGEX CONSTRAINTS
// ============================================

// Only numbers
Router::get('product/{id:\d+}', function($id) {
    echo "Product ID: {$id}";
});

// Only letters
Router::get('category/{slug:[a-z-]+}', function($slug) {
    echo "Category: {$slug}";
});

// Custom regex
Router::get('post/{year:[0-9]{4}}/{month:[0-9]{2}}/{slug}', function($year, $month, $slug) {
    echo "Post: {$slug} from {$month}/{$year}";
});

// ============================================
// OPTIONAL PARAMETERS
// ============================================

Router::get('blog/{id:\d+}[/{title}/]', function($id, $title = null) {
    echo "Blog ID: {$id}";
    if ($title) {
        echo " - Title: {$title}";
    }
});

// Nested optional parameters
Router::get('archive/{year:\d{4}}[/{month:\d{2}}[/{day:\d{2}}/]/]', function($year, $month = null, $day = null) {
    echo "Archive: {$year}";
    if ($month) echo "/{$month}";
    if ($day) echo "/{$day}";
});
