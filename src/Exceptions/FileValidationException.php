<?php
declare(strict_types=1);

namespace Scaleplan\Helpers\Exceptions;

/**
 * Class FileSaveException
 *
 * @package Scaleplan\Helpers\Exceptions
 */
class FileValidationException extends FileException
{
    public const MESSAGE = 'Ошибка валидации файла.';
    public const CODE = 422;
}
