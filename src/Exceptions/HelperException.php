<?php

namespace Scaleplan\Helpers\Exceptions;

/**
 * Class HelperException
 *
 * @package Scaleplan\Helpers\Exceptions
 */
class HelperException extends \Exception
{
    public const MESSAGE = 'Ошибка хэлпера.';
    public const CODE = 500;

    /**
     * HelperException constructor.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message ?: static::MESSAGE, $code ?: static::CODE, $previous);
    }
}
