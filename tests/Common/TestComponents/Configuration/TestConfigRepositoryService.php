<?php

namespace CleverReach\Tests\Common\TestComponents\Configuration;

use CleverReach\Infrastructure\Interfaces\Required\ConfigRepositoryInterface;

class TestConfigRepositoryService implements ConfigRepositoryInterface
{
    /**
     * @var array
     */
    private static $configurationStorage = array();

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    public function get($key)
    {
        if (array_key_exists($key, self::$configurationStorage)) {
            return self::$configurationStorage[$key];
        }

        return null;
    }

    /**
     * @param string $key
     * @param bool|int|string $value
     *
     * @return bool
     */
    public function set($key, $value)
    {
        self::$configurationStorage[$key] = $value;

        return true;
    }

    /**
     * Clears storage.
     */
    public function flush()
    {
        self::$configurationStorage = array();
    }
}