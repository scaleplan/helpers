<?php

namespace Scaleplan\Helpers\Exceptions;

use function Scaleplan\Translator\translate;

/**
 * Class SettingNotFoundException
 *
 * @package Scaleplan\Main\Exceptions
 */
class EnvNotFoundException extends HelperException
{
    public const MESSAGE = 'helpers.env-not-found';
    public const CODE = 404;

    /**
     * EnvNotFoundException constructor.
     *
     * @param string $envName
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     *
     * @throws \ReflectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ContainerTypeNotSupportingException
     * @throws \Scaleplan\DependencyInjection\Exceptions\DependencyInjectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ParameterMustBeInterfaceNameOrClassNameException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ReturnTypeMustImplementsInterfaceException
     */
    public function __construct(string $envName = '', string $message = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct(
            $message ?: translate(static::MESSAGE, ['env-name' => $envName,]) ?: static::MESSAGE,
            $code ?: static::CODE,
            $previous
        );
    }
}
