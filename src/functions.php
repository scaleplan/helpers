<?php

namespace Scaleplan\Helpers;

use Scaleplan\Helpers\Exceptions\EnvNotFoundException;

/**
 * @param string $envName
 *
 * @return string|null
 */
function get_env(string $envName) : ?string
{
    $env = getenv($envName);
    if ($env === false) {
        return null;
    }

    return $env;
}

/**
 * @param string $envName
 *
 * @return string
 *
 * @throws EnvNotFoundException
 * @throws \ReflectionException
 * @throws \Scaleplan\DependencyInjection\Exceptions\ContainerTypeNotSupportingException
 * @throws \Scaleplan\DependencyInjection\Exceptions\DependencyInjectionException
 * @throws \Scaleplan\DependencyInjection\Exceptions\ParameterMustBeInterfaceNameOrClassNameException
 * @throws \Scaleplan\DependencyInjection\Exceptions\ReturnTypeMustImplementsInterfaceException
 */
function get_required_env(string $envName) : string
{
    $env = getenv($envName);
    if ($env === false) {
        throw new EnvNotFoundException($envName);
    }

    return $env;
}
