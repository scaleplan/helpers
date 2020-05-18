<?php
declare(strict_types=1);

namespace Scaleplan\Helpers\Exceptions;

/**
 * Class FileException
 *
 * @package Scaleplan\Helpers\Exceptions
 */
class FileException extends HelperException
{
    public const MESSAGE = 'File error.';
    public const CODE = 500;
}
