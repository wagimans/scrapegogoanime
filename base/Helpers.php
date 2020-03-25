<?php

namespace base;

class Helpers
{
    public static function isCli()
    {
        return php_sapi_name() === 'cli';
    }

    public static function getVal($var, $key, $default = null)
    {
        if (isset($var[$key])) return $var[$key];
        if (is_object($var)) if (isset($var->$key)) return $var->$key;
        return $default;
    }

    public static function extractNode($node, $query, array $attr, $qty = 0)
    {
        if (!$query) return self::getVal($node->extract($attr), $qty);
        return self::getVal($node->filter($query)->extract($attr), $qty);
    }
}
