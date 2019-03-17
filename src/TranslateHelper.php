<?php

namespace App\Classes;

use function Scaleplan\Helpers\get_env;

/**
 * Хэлпер для перевода текста посредством Yandex.Переводчика
 *
 * Class TranslateHelper
 *
 * @package App\Classes
 */
class TranslateHelper
{
    /**
     * Ключ к API Yandex Translate
     */
    public const YANDEX_TRANSLATE_API_KEY = ''; //trnsl.1.1.201...

    /**
     * Ссылка на Yandex.Переводчик для перевода
     */
    public const TRANSLATE_URL = 'https://translate.yandex.net/api/v1.5/tr.json/translate';

    /**
     * Ссылка на Yandex.Переводчик для отределения языка текста
     */
    public const LANG_DETECT_URL = 'https://translate.yandex.net/api/v1.5/tr.json/detect';

    /**
     * @return string
     */
    public static function getApiKey() : string
    {
        return get_env('YANDEX_TRANSLATE_API_KEY') ?? static::YANDEX_TRANSLATE_API_KEY;
    }

    /**
     * Вернуть язык сообщения
     *
     * @param string $message
     *
     * @return string
     */
    public static function getMessageLang(string $message): string
    {
        $currentLang = file_get_contents(
            static::LANG_DETECT_URL . '
            ?key=' . static::getApiKey() . '
            &text=' . $message . '
            &hint=en,ru'
        );

        return json_decode($currentLang, true)['lang'] ?? $message;
    }

    /**
     * Перевести сообщение на выбранный язык
     *
     * @param string $message - сообщение
     * @param string $toLang - на какой язык переводить
     * @param string|NULL $fromLang - с какого языка переводить
     *
     * @return string
     */
    public static function translate(string $message, string $toLang, string $fromLang = 'ru'): string
    {
        if ($fromLang === $toLang) {
            return $message;
        }

        if ($fromLang) {
            $requestLang = "$fromLang-$toLang";
        } else {
            $requestLang = $toLang;
        }

        $params = [
            'key' => static::getApiKey(),
            'text' => $message,
            'lang' => $requestLang,
            'format' => 'html'
        ];

        $translatedMessage = file_get_contents(static::TRANSLATE_URL . '?' . http_build_query($params));

        return json_decode($translatedMessage, true)['text'][0] ?? $message;
    }
}
