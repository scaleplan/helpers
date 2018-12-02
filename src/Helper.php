<?php

namespace Scaleplan\Helpers;

use Scaleplan\Db\Db;
use Scaleplan\Helpers\Exceptions\HelperException;
use Scaleplan\Helpers\Exceptions\YoutubeException;

/**
 * Полезные методы
 *
 * Class Helper
 *
 * @package Scaleplan\Helpers
 */
class Helper
{
    public const YOUTUBE_INFO_URL = 'https://www.youtube.com/get_video_info?video_id=';

    public const DEFAULT_CONFIGS_DIRECTORY_PATH = '/configs';

    public const DEFAULT_LIST_LIMIT = 10;

    public const DOMAIN_ENV_LABEL = 'DOMAIN';

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
    public static function indexingArray(array $array, string $field): array
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
     * Убрать из ответа NULL-значения
     *
     * @param $data - набор данных для очистки
     *
     * @return array
     */
    public static function disableNulls(& $data): array
    {
        if (\is_array($data)) {
            $offNull = function (& $value) {
                return $value === null ? '' : $value;
            };
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
     * Корректный доступ к конфигу
     *
     * @param string $name - название конфига
     *
     * @return array
     *
     * @throws HelperException
     */
    public static function getConf(string $name): array
    {
        $configPath = getenv('DEFAULT_CONFIGS_DIRECTORY_PATH') ?? static::DEFAULT_CONFIGS_DIRECTORY_PATH;

        if (empty($_SESSION[$name])) {
            $filePath = "{$_SERVER['DOCUMENT_ROOT']}/$configPath/$name.php";
            if (!file_exists($filePath)) {
                throw new HelperException("Файл $filePath не существует");
            }

            $_SESSION[$name] = include $filePath;
        }

        return $_SESSION[$name];
    }

    /**
     * Отправить коммит во все подключения к РСУБД
     *
     * @param Db[] $databases
     */
    public static function allDBCommit(array $databases): void
    {
        foreach ($databases as $db) {
            if ($db instanceof Db) {
                $db->commit();
            }
        }
    }

    /**
     * Отправить роллбэк во все подключения к РСУБД
     *
     * @param Db[] $databases
     */
    public static function allDBRollback(array $databases): void
    {
        foreach ($databases as $db) {
            if ($db instanceof Db) {
                $db->rollBack();
            }
        }
    }

    /**
     * Почистить строку с номером телефона
     *
     * @param string $phoneNumber - строка с номером телефона
     *
     * @return string
     */
    public static function trimPhoneNumber(string &$phoneNumber): string
    {
        return $phoneNumber ? strtr($phoneNumber, [' ' => '', '(' => '', ')' => '', '-' => '']) : $phoneNumber;
    }

    /**
     * Возвращает домен 3-го уровня
     *
     * @param string|null $url - URL для выделения поддомена
     *
     * @return null|string
     */
    public static function getSubdomain(string $url = null): ?string
    {
        if (!$url) {
            $url = $_SERVER['REQUEST_URI'];
        }

        $url = parse_url($url, PHP_URL_HOST) ?? $url;
        $url = str_replace('www.', '', $url);
        $domains = explode('.', $url);
        if (\count($domains) < 3) {
            return '';
        }

        return idn_to_utf8(array_reverse($domains)[2], 0, INTL_IDNA_VARIANT_UTS46);
    }

    /**
     * Рекурсивно заменить ключи массива
     *
     * @param array $array - массив под замену
     *
     * @param array $replaceArray - массив замен в формате <старый ключ> => <новый ключ>
     */
    public static function arrayReplaceRecursive(array &$array, array $replaceArray): void
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
     * Установить значение максимального количества возвращаемых полей
     *
     * @param int|null $limit
     */
    public static function setLimit(?int &$limit): void
    {
        if (!$limit) {
            $limit = getenv('DEFAULT_LIST_LIMIT') ?? static::DEFAULT_LIST_LIMIT;
        }
    }

    /**
     * Получить информацию о ролике с Youtube
     *
     * @param string $videoId - иденетификатор видео
     *
     * @return array
     *
     * @throws HelperException
     */
    public static function getYoutubeInfo(string $videoId): array
    {
        $info = file_get_contents(static::YOUTUBE_INFO_URL . $videoId);
        if (!$info || stripos($info, 'status=fail') !== false) {
            throw new YoutubeException('Не удалось получить информацию о видеоролике');
        }

        $info = explode('&', $info);
        $newInfo = [];

        foreach ($info as $record) {
            $record = explode('=', $record);
            $newInfo[$record[0]] = urldecode($record[1]);
        }

        $sources = explode(',', $newInfo['url_encoded_fmt_stream_map']);
        foreach ($sources as &$source) {
            $streams = [];
            foreach (explode('&', $source) as $record) {
                $record = explode('=', $record);
                $streams[$record[0]] = urldecode($record[1]);
            }

            $source = $streams;
        }

        unset($source);

        return [
            'title' => str_replace('+', '', $newInfo['title'] ?? ''),
            'poster' => str_replace(
                'default.jpg',
                'maxresdefault.jpg',
                $newInfo['thumbnail_url'] ?? ''
            ),
            'sources' => $sources
        ];
    }

    /**
     * Проверка хоста, например, $_SERVER['HTTP_HOST'] на уязвимость.
     * Подробнее на https://expressionengine.com/blog/http-host-and-server-name-security-issues
     *
     * @param string $host
     *
     * @return bool
     */
    public static function hostCheck(string $host): bool
    {
        return getenv(static::DOMAIN_ENV_LABEL)
            && strpos($host, getenv(static::DOMAIN_ENV_LABEL)) !== false;
    }
}

/**
 * @param string $envName
 *
 * @return mixed|null
 */
function getenv(string $envName) {
    $env = getenv($envName);
    return $env === false ? null : $env;
}