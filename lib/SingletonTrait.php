<?php

namespace module\lib;

trait SingletonTrait
{

    private static $singleton;

    /**
     * @param mixed ...$args
     * @return static
     */
    public static function getInstance(...$args)
    {
        if (static::$singleton === null) {
            static::$singleton = new static(...$args);
        }
        return static::$singleton;
    }

}