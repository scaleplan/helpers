<?php

namespace Scaleplan\Helpers;

/**
 * Class NameConverter
 *
 * @package Scaleplan\Helpers
 */
class NameConverter
{
    /**
     * @param string $snake
     *
     * @return string
     */
    public static function snakeCaseToLowerCamelCase(string $snake) : string
    {
        return lcfirst(static::snakeCaseToCamelCase($snake));
    }

    /**
     * @param string $snake
     *
     * @return string
     */
    public static function snakeCaseToCamelCase(string $snake) : string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $snake)));
    }

    /**
     * @param string $snake
     *
     * @return string
     */
    public static function kebabCaseToLowerCamelCase(string $snake) : string
    {
        return lcfirst(static::snakeCaseToCamelCase($snake));
    }

    /**
     * @param string $snake
     *
     * @return string
     */
    public static function kebabCaseToCamelCase(string $snake) : string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $snake)));
    }

    /**
     * @param string $camel
     *
     * @return string
     */
    public static function camelCaseToSnakeCase(string $camel) : string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $camel));
    }

    /**
     * @param string $camel
     *
     * @return string
     */
    public static function camelCaseToKebabCase(string $camel) : string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $camel));
    }
}
