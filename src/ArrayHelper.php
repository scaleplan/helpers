<?php

namespace Scaleplan\Helpers;

use Scaleplan\Helpers\Exceptions\HelperException;

/**
 * Class ArrayHelper
 *
 * @package Scaleplan\Helpers
 */
class ArrayHelper
{
    /**
     * Индексирует массив записей в соответствии с одним из полей
     *
     * @param array $array - индексируемый массив
     * @param string $field - имя поля
     *
     * @return array
     *
     * @throws \Exception
     */
    public static function indexingArray(array $array, string $field) : array
    {
        foreach ($array as $key => &$value) {
            if (!\is_int($key)) {
                continue;
            }

            if (!isset($value[$field])) {
                throw new HelperException('Запись не имеет искомого индекса');
            }

            $array[$value[$field]] = $value;
            unset($value[$field], $array[$key]);
        }

        unset($value);

        return $array;
    }

    /**
     * Убрать из массива NULL-значения
     *
     * @param $data - набор данных для очистки
     *
     * @return array
     */
    public static function disableNulls(& $data) : array
    {
        $offNull = static function (& $value) {
            return $value === null ?? $value;
        };
        if (\is_array($data)) {
            if (isset($data[0]) && \is_array($data[0])) {
                foreach ($data as $key => & $value) {
                    $value = array_map($offNull, $value);
                }
                unset($value);
            } else {
                $data = array_map($offNull, $data);
            }
        }
        return $data;
    }

    /**
     * Рекурсивно заменить ключи массива
     *
     * @param array $array - массив под замену
     *
     * @param array $replaceArray - массив замен в формате <старый ключ> => <новый ключ>
     */
    public static function arrayReplaceRecursive(array &$array, array $replaceArray) : void
    {
        foreach ($array as $key => &$value) {
            if (\is_array($value)) {
                static::arrayReplaceRecursive($value, $replaceArray);
            }

            if (array_key_exists($key, $replaceArray)) {
                $array[$replaceArray[$key]] = $value;
                unset($array[$key]);
            }
        }

        unset($value);
    }

    /**
     * @param array $array
     *
     * @return bool
     */
    public static function isAccos(array $array) : bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }
}
