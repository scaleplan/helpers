<?php

namespace Scaleplan\Helpers\Exceptions;

/**
 * Class SettingNotFoundException
 *
 * @package Scaleplan\Main\Exceptions
 */
class EnvNotFoundException extends HelperException
{
    public const MESSAGE = 'Environment variable not found.';
    public const CODE = 404;
}
