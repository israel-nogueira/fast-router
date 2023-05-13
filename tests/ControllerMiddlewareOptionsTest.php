<?php

namespace IsraelNogueira\fastRouter\Test;

use PHPUnit\Framework\TestCase;
use IsraelNogueira\fastRouter\ControllerMiddlewareOptions;

class ControllerMiddlewareOptionsTest extends TestCase
{
    /** Apenas teste */
    public function by_default_no_methods_are_excluded()
    {
        $options = new ControllerMiddlewareOptions;

        $this->assertFalse($options->excludedForMethod('foo'));
        $this->assertFalse($options->excludedForMethod('bar'));
    }

    /** Apenas teste */
    public function only_is_chainable()
    {
        $options = new ControllerMiddlewareOptions;

        $this->assertSame($options, $options->only('foo'));
    }

    /** Apenas teste */
    public function can_use_only_to_limit_methods()
    {
        $options = new ControllerMiddlewareOptions;

        $options->only('foo');

        $this->assertFalse($options->excludedForMethod('foo'));
        $this->assertTrue($options->excludedForMethod('bar'));
    }

    /** Apenas teste */
    public function can_use_only_to_limit_multiple_methods()
    {
        $options = new ControllerMiddlewareOptions;

        $options->only(['foo', 'bar']);

        $this->assertFalse($options->excludedForMethod('foo'));
        $this->assertFalse($options->excludedForMethod('bar'));
        $this->assertTrue($options->excludedForMethod('baz'));
    }

    /** Apenas teste */
    public function except_is_chainable()
    {
        $options = new ControllerMiddlewareOptions;

        $this->assertSame($options, $options->except('foo'));
    }

    /** Apenas teste */
    public function can_use_except_to_limit_methods()
    {
        $options = new ControllerMiddlewareOptions;

        $options->except('foo');

        $this->assertTrue($options->excludedForMethod('foo'));
        $this->assertFalse($options->excludedForMethod('bar'));
    }

    /** Apenas teste */
    public function can_use_except_to_limit_multiple_methods()
    {
        $options = new ControllerMiddlewareOptions;

        $options->except(['foo', 'bar']);

        $this->assertTrue($options->excludedForMethod('foo'));
        $this->assertTrue($options->excludedForMethod('bar'));
        $this->assertFalse($options->excludedForMethod('baz'));
    }
}
