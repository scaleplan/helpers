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
    return $env === false ? null : $env;
}

/**
 * @param string $envName
 *
 * @return string
 *
 * @throws EnvNotFoundException
 */
function get_required_env(string $envName) : string
{
    $env = getenv($envName);
    if ($env === false) {
        throw new EnvNotFoundException();
    }

    return $env;
}