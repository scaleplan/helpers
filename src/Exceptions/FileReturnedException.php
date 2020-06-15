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
    public const MESSAGE = 'Ошибка формирования файла.';
    public const CODE = 500;
}
