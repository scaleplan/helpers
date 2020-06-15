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
    public const MESSAGE = 'Ошибка файла.';
    public const CODE = 500;
}
