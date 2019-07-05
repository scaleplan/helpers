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
    public const YOUTUBE_STATUS_FAIL = 'status=fail';
    public const YOUTUBE_URL_ENCODED = 'url_encoded_fmt_stream_map';
    public const YOUTUBE_TITLE = 'title';
    public const YOUTUBE_IMG_NAME = 'default.jpg';
    public const YOUTUBE_MAX_IMG_NAME = 'maxresdefault.jpg';
    public const YOUTUBE_THUMBNAIL_URL = 'thumbnail_url';

    public const DEFAULT_CONFIGS_DIRECTORY_PATH = '/configs';

    public const DEFAULT_LIST_LIMIT = 10;

    public const DOMAIN_ENV_LABEL = 'DOMAIN';

    /**
     * Корректный доступ к конфигу
     *
     * @param string $name - название конфига
     *
     * @return array
     *
     * @throws HelperException
     */
    public static function getConf(string $name) : array
    {
        $configPath = get_env('DEFAULT_CONFIGS_DIRECTORY_PATH') ?? static::DEFAULT_CONFIGS_DIRECTORY_PATH;

        if (empty($_SESSION[$name])) {
            $filePath = get_required_env('BUNDLE_PATH') . "/$configPath/$name.php";
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
     * @param array $databases
     *
     * @throws \Scaleplan\Db\Exceptions\PDOConnectionException
     */
    public static function allDBCommit(array $databases) : void
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
     *
     * @throws \Scaleplan\Db\Exceptions\PDOConnectionException
     */
    public static function allDBRollback(array $databases) : void
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
    public static function trimPhoneNumber(string $phoneNumber) : string
    {
        return $phoneNumber ? strtr($phoneNumber, [' ' => '', '(' => '', ')' => '', '-' => '']) : $phoneNumber;
    }

    /**
     * Возвращает домен 3-го уровня
     *
     * @param string|null $url - URL для выделения поддомена
     *
     * @return string
     */
    public static function getSubdomain(string $url = null) : string
    {
        if (!$url) {
            $url = (string)$_SERVER['HTTP_HOST'];
        }

        $url = parse_url($url, PHP_URL_HOST) ?? $url;
        /** @var string $url */
        $url = \str_replace('www.', '', $url);
        $domains = explode('.', $url);
        if (\count($domains) < 3) {
            return '';
        }

        return idn_to_utf8(array_reverse($domains)[2], 0, INTL_IDNA_VARIANT_UTS46);
    }

    /**
     * Установить значение максимального количества возвращаемых полей
     *
     * @param int|null $limit
     */
    public static function setLimit(?int &$limit) : void
    {
        if (!$limit) {
            $limit = (int)(get_env('DEFAULT_LIST_LIMIT') ?? static::DEFAULT_LIST_LIMIT);
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
    public static function getYoutubeInfo(string $videoId) : array
    {
        $info = file_get_contents(static::YOUTUBE_INFO_URL . $videoId);
        if (!$info || stripos($info, static::YOUTUBE_STATUS_FAIL) !== false) {
            throw new YoutubeException('Не удалось получить информацию о видеоролике');
        }

        $info = explode('&', $info);
        $newInfo = [];

        foreach ($info as $record) {
            $record = explode('=', $record);
            $newInfo[$record[0]] = urldecode($record[1]);
        }

        $sources = explode(',', $newInfo[static::YOUTUBE_URL_ENCODED]);
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
            'title'   => str_replace('+', '', $newInfo[static::YOUTUBE_TITLE] ?? ''),
            'poster'  => str_replace(
                static::YOUTUBE_IMG_NAME,
                static::YOUTUBE_MAX_IMG_NAME,
                $newInfo[static::YOUTUBE_THUMBNAIL_URL] ?? ''
            ),
            'sources' => $sources,
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
    public static function hostCheck(string $host = null) : bool
    {
        if (!$host) {
            $host = (string)$_SERVER['HTTP_HOST'];
        }

        return getenv(static::DOMAIN_ENV_LABEL) !== false
            && strrpos(strrev($host), strrev(getenv(static::DOMAIN_ENV_LABEL))) === 0;
    }

    /**
     * @param string $url
     * @param array $params
     *
     * @return string
     */
    public static function buildUrl(string $url, array $params) : string
    {
        if ($params) {
            $url = "$url?" . http_build_query($params);
        }

        return $url;
    }
}
