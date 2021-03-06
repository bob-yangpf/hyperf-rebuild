<?php


namespace Rebuild\Config;


use Rebuild\Contract\ConfigInterface;

class Config implements ConfigInterface
{
    protected $configs = [];

    public function __construct($configs)
    {
        $this->configs = $configs;
    }


    /**
     * @inheritDoc
     */
    public function get(string $key, $default = null)
    {
        return $this->configs[$key] ?? $default;
    }

    /**
     * @inheritDoc
     */
    public function has(string $keys)
    {
        return isset($this->configs[$keys]);
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, $value)
    {
        $this->configs[$key] = $value;
    }
}