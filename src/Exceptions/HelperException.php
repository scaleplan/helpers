<?php

namespace Scaleplan\Helpers\Exceptions;

/**
 * Class HelperException
 *
 * @package Scaleplan\Helpers\Exceptions
 */
class HelperException extends \Exception
{
    public const MESSAGE = 'Helper error.';

    /**
     * HelperException constructor.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(\string $message = '', \int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message ?: static::MESSAGE, $code, $previous);
    }
}
