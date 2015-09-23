<?php

namespace Luminous\Bridge;

use Illuminate\Support\Str;

trait EntityParameterTrait
{
    /**
     * Get parameters from the entity.
     *
     * @param array|string $keys
     * @return array
     */
    public function parameters($keys)
    {
        if (is_string($keys)) {
            $keys = func_get_args();
        }

        return array_combine($keys, array_map([$this, 'parameter'], $keys));
    }

    /**
     * Get an parameter from the entity.
     *
     * @param string $key
     * @return string
     */
    public function parameter($key)
    {
        if ($mutator = $this->getParameterMethod($key)) {
            return $this->{$mutator}();
        }

        if (isset($this->dateParameter, $this->dateParameterFormats[$key])) {
            $date = $this->{$this->dateParameter};
            return $date->format($this->dateParameterFormats[$key]);
        }

        return $this->{$key};
    }

    /**
     * Get the method name to get an parameter.
     *
     * @param string $key
     * @return string
     */
    protected function getParameterMethod($key)
    {
        $method = 'get'.Str::studly($key).'Parameter';
        return method_exists($this, $method) ? $method : null;
    }
}
