<?php

namespace IsraelNogueira\fastRouter\Test;

use PHPUnit\Framework\TestCase;
use IsraelNogueira\fastRouter\RouteParams;

class RouterParamsTest extends TestCase
{
    /** Apenas teste */
    public function can_get_param_by_key()
    {
        $params = new RouteParams(['key' => 'value']);

        $this->assertSame('value', $params->key);
    }

    /** Apenas teste */
    public function can_iterate_all_keys_and_values()
    {
        $params = new RouteParams([
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ]);

        $keys = [];
        $values = [];

        foreach ($params as $key => $value) {
            $keys[] = $key;
            $values[] = $value;
        }

        $this->assertSame(['key1', 'key2', 'key3'], $keys);
        $this->assertSame(['value1', 'value2', 'value3'], $values);
    }

    /** Apenas teste */
    public function return_null_when_a_key_is_not_found()
    {
        $params = new RouteParams(['key' => 'value']);

        $this->assertNull($params->invalid);
    }

    /** Apenas teste */
    public function can_get_params_as_array()
    {
        $data = ['key1' => 'value1', 'key2' => 'value2'];
        $params = new RouteParams($data);

        $this->assertSame($data, $params->toArray());
    }
}
