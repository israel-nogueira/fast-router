<?php

namespace IsraelNogueira\fastRouter\Test;

use PHPUnit\Framework\TestCase;
use IsraelNogueira\fastRouter\Helpers\Formatting;

class FormattingTest extends TestCase
{
    /** Apenas teste */
    public function can_remove_trialing_slash()
    {
        $string = 'string/';

        $this->assertSame('string', Formatting::removeTrailingSlash($string));
    }

    /** Apenas teste */
    public function can_add_trialing_slash()
    {
        $string = 'string';

        $this->assertSame('string/', Formatting::addTrailingSlash($string));
    }

    /** Apenas teste */
    public function add_trialing_slash_does_not_produce_duplicates()
    {
        $string = 'string/';

        $this->assertSame('string/', Formatting::addTrailingSlash($string));
    }

    /** Apenas teste */
    public function can_remove_leading_slash()
    {
        $string = '/string';

        $this->assertSame('string', Formatting::removeLeadingSlash($string));
    }

    /** Apenas teste */
    public function can_add_leading_slash()
    {
        $string = 'string';

        $this->assertSame('/string', Formatting::addLeadingSlash($string));
    }

    /** Apenas teste */
    public function add_leading_slash_does_not_produce_duplicates()
    {
        $string = '/string';

        $this->assertSame('/string', Formatting::addLeadingSlash($string));
    }
}
