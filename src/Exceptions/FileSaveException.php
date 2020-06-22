<?php
declare(strict_types=1);

namespace Scaleplan\Helpers\Exceptions;

/**
 * Class FileSaveException
 *
 * @package Scaleplan\Helpers\Exceptions
 */
class FileSaveException extends FileException
{
    public const MESSAGE = 'helpers.file-saving-error';
    public const CODE = 500;
}
