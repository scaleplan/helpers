<?php
declare(strict_types=1);

namespace Scaleplan\Helpers\Exceptions;

/**
 * Class FileUploadException
 *
 * @package Scaleplan\Helpers\Exceptions
 */
class FileUploadException extends FileException
{
    public const MESSAGE = 'helpers.file-upload-error';
    public const CODE = 500;
}
