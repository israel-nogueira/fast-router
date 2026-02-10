<?php

declare(strict_types=1);

namespace IsraelNogueira\FastRouter\Tests;

use PHPUnit\Framework\TestCase;
use IsraelNogueira\FastRouter\Router;
use IsraelNogueira\FastRouter\Route;
use IsraelNogueira\FastRouter\RouteParser;

/**
 * Router Test Suite
 * 
 * Example test file showing how to test Fast Router
 */
class RouterTest extends TestCase
{
    private Router $router;
    
    protected function setUp(): void
    {
        $this->router = new Router();
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }
    
    protected function tearDown(): void
    {
        $this->router->clear();
    }
    
    // ============================================
    // BASIC ROUTING TESTS
    // ============================================
    
    public function testBasicGetRoute(): void
    {
        $this->router->get('test', function() {
            return 'success';
        });
        
        $result = $this->router->dispatch('GET', '/test');
        
        $this->assertEquals('success', $result);
    }
    
    public function testBasicPostRoute(): void
    {
        $this->router->post('submit', function() {
            return 'posted';
        });
        
        $result = $this->router->dispatch('POST', '/submit');
        
        $this->assertEquals('posted', $result);
    }
    
    public function testAnyMethodRoute(): void
    {
        $this->router->any('endpoint', function() {
            return 'any';
        });
        
        $this->assertEquals('any', $this->router->dispatch('GET', '/endpoint'));
        $this->assertEquals('any', $this->router->dispatch('POST', '/endpoint'));
        $this->assertEquals('any', $this->router->dispatch('PUT', '/endpoint'));
    }
    
    // ============================================
    // PARAMETER TESTS
    // ============================================
    
    public function testSimpleParameter(): void
    {
        $this->router->get('user/{id}', function($id) {
            return "User: {$id}";
        });
        
        $result = $this->router->dispatch('GET', '/user/123');
        
        $this->assertEquals('User: 123', $result);
    }
    
    public function testMultipleParameters(): void
    {
        $this->router->get('post/{year}/{month}/{slug}', function($year, $month, $slug) {
            return "{$year}-{$month}-{$slug}";
        });
        
        $result = $this->router->dispatch('GET', '/post/2024/12/hello-world');
        
        $this->assertEquals('2024-12-hello-world', $result);
    }
    
    public function testRegexConstraint(): void
    {
        $this->router->get('product/{id:\d+}', function($id) {
            return "Product: {$id}";
        });
        
        // Should match
        $result = $this->router->dispatch('GET', '/product/123');
        $this->assertEquals('Product: 123', $result);
        
        // Should not match (letters)
        $result = $this->router->dispatch('GET', '/product/abc');
        $this->assertNull($result);
    }
    
    public function testOptionalParameter(): void
    {
        $this->router->get('blog/{id}[/{title}/]', function($id, $title = null) {
            return $title ? "{$id}:{$title}" : $id;
        });
        
        // Without optional parameter
        $result = $this->router->dispatch('GET', '/blog/123');
        $this->assertEquals('123', $result);
        
        // With optional parameter
        $result = $this->router->dispatch('GET', '/blog/123/hello');
        $this->assertEquals('123:hello', $result);
    }
    
    // ============================================
    // GROUP TESTS
    // ============================================
    
    public function testSimpleGroup(): void
    {
        $this->router->group('admin', function($router) {
            $router->get('dashboard', function() {
                return 'admin-dashboard';
            });
        });
        
        $result = $this->router->dispatch('GET', '/admin/dashboard');
        
        $this->assertEquals('admin-dashboard', $result);
    }
    
    public function testNestedGroups(): void
    {
        $this->router->group('admin', function($router) {
            $router->group('products', function($router) {
                $router->get('list', function() {
                    return 'admin-products-list';
                });
            });
        });
        
        $result = $this->router->dispatch('GET', '/admin/products/list');
        
        $this->assertEquals('admin-products-list', $result);
    }
    
    // ============================================
    // MIDDLEWARE TESTS
    // ============================================
    
    public function testMiddlewareExecution(): void
    {
        $executed = [];
        
        $middleware1 = new class {
            public function handle(array $returns, callable $next) use (&$executed): mixed
            {
                $executed[] = 'middleware1';
                return $next($returns);
            }
        };
        
        $this->router->get([
            'prefix' => 'test',
            'middleware' => [get_class($middleware1)]
        ], function() use (&$executed) {
            $executed[] = 'route';
            return 'success';
        });
        
        // Note: This test would need proper middleware setup
        // Just demonstrating the concept
    }
    
    // ============================================
    // CONTROLLER TESTS
    // ============================================
    
    public function testControllerArraySyntax(): void
    {
        $controller = new class {
            public function show($id) {
                return "Controller: {$id}";
            }
        };
        
        $this->router->get('user/{id}', [get_class($controller), 'show']);
        
        $result = $this->router->dispatch('GET', '/user/456');
        
        $this->assertEquals('Controller: 456', $result);
    }
    
    // ============================================
    // ROUTE PARSER TESTS
    // ============================================
    
    public function testRouteParserSimple(): void
    {
        $result = RouteParser::match('user/{id}', 'user/123');
        
        $this->assertTrue($result['status']);
        $this->assertEquals('123', $result['params']['id']);
    }
    
    public function testRouteParserRegex(): void
    {
        $result = RouteParser::match('product/{id:\d+}', 'product/789');
        
        $this->assertTrue($result['status']);
        $this->assertEquals('789', $result['params']['id']);
        
        // Should not match non-digits
        $result = RouteParser::match('product/{id:\d+}', 'product/abc');
        $this->assertFalse($result['status']);
    }
    
    public function testRouteParserOptional(): void
    {
        // With optional parameter
        $result = RouteParser::match('blog/{id}[/{title}/]', 'blog/123/hello');
        $this->assertTrue($result['status']);
        $this->assertEquals('123', $result['params']['id']);
        $this->assertEquals('hello', $result['params']['title']);
        
        // Without optional parameter
        $result = RouteParser::match('blog/{id}[/{title}/]', 'blog/123');
        $this->assertTrue($result['status']);
        $this->assertEquals('123', $result['params']['id']);
        $this->assertNull($result['params']['title']);
    }
    
    public function testExtractParamNames(): void
    {
        $names = RouteParser::extractParamNames('user/{id}/posts/{slug}');
        
        $this->assertEquals(['id', 'slug'], $names);
    }
    
    public function testExtractParamNamesWithRegex(): void
    {
        $names = RouteParser::extractParamNames('product/{id:\d+}/review/{rating:[1-5]}');
        
        $this->assertEquals(['id', 'rating'], $names);
    }
    
    // ============================================
    // ROUTE COLLECTION TESTS
    // ============================================
    
    public function testRouteCollectionCount(): void
    {
        $this->router->get('route1', fn() => '1');
        $this->router->get('route2', fn() => '2');
        $this->router->get('route3', fn() => '3');
        
        $this->assertEquals(3, $this->router->getRoutes()->count());
    }
    
    public function testRouteCollectionClear(): void
    {
        $this->router->get('test', fn() => 'test');
        $this->assertEquals(1, $this->router->getRoutes()->count());
        
        $this->router->clear();
        $this->assertEquals(0, $this->router->getRoutes()->count());
    }
    
    // ============================================
    // EDGE CASES
    // ============================================
    
    public function testEmptyRoute(): void
    {
        $this->router->get('', function() {
            return 'root';
        });
        
        $result = $this->router->dispatch('GET', '/');
        
        $this->assertEquals('root', $result);
    }
    
    public function testTrailingSlash(): void
    {
        $this->router->get('test/', function() {
            return 'with-slash';
        });
        
        // Should match with or without trailing slash
        $result = $this->router->dispatch('GET', '/test');
        $this->assertEquals('with-slash', $result);
    }
    
    public function testNoMatchReturnsNull(): void
    {
        $this->router->get('existing', fn() => 'exists');
        
        $result = $this->router->dispatch('GET', '/non-existing');
        
        $this->assertNull($result);
    }
    
    public function testContinueFlag(): void
    {
        $executions = [];
        
        $this->router->get([
            'prefix' => 'test',
            'continue' => true
        ], function() use (&$executions) {
            $executions[] = 'first';
            return 'first';
        });
        
        $this->router->get('test', function() use (&$executions) {
            $executions[] = 'second';
            return 'second';
        });
        
        $result = $this->router->dispatch('GET', '/test');
        
        $this->assertEquals(['first', 'second'], $executions);
        $this->assertEquals('second', $result);
    }
}
