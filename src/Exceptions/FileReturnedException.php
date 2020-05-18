<?php
declare(strict_types=1);

namespace Scaleplan\Helpers\Exceptions;

/**
 * Class FileReturnedException
 *
 * @package Scaleplan\Helpers\Exceptions
 */
class FileReturnedException extends FileException
{
    public const MESSAGE = 'File returned error.';
    public const CODE = 500;
}
