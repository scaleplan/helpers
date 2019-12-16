<?php

namespace Scaleplan\Helpers\Exceptions;

/**
 * Class SettingNotFoundException
 *
 * @package Scaleplan\Main\Exceptions
 */
class EnvNotFoundException extends HelperException
{
    public const MESSAGE = 'Environment variable :envName not found.';
    public const CODE = 404;

    /**
     * EnvNotFoundException constructor.
     *
     * @param string $envName
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $envName = '', string $message = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct(
            str_replace(':envName', $envName, $message ?: static::MESSAGE),
            $code ?: static::CODE,
            $previous
        );
    }
}
