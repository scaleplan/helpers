<?php

namespace Scaleplan\Helpers;

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Хэлпер отправки писем
 *
 * Class MailerHelper
 *
 * @package App\Classes
 */
class MailerHelper
{
    /* Настроки PHPMailer */

    /**
     * Язык писем
     */
    public const MAIL_LANG = 'ru';

    /**
     * Кодировка писем
     */
    public const MAIL_CHARSET = 'UTF-8';

    /**
     * Адрес SMTP-сервера
     */
    public const MAIL_HOST = 'smtp.yandex.ru';

    /**
     * Логин для авторизации на SMTP-сервере
     */
    public const MAIL_USERNAME = 'user@domain.com';

    /**
     * Пароль для авторизации на SMTP-сервере
     */
    public const MAIL_PASSWORD = 'password';

    /**
     * Порт для подключения к SMTP-серверу
     */
    public const MAIL_PORT = 465;

    /**
     * Обратный адрес писем
     */
    public const MAIL_FROM = 'user@domain.com';

    /**
     * Имя отправителя
     */
    public const MAIL_FROM_NAME = 'domain.com';

    /**
     * Куда присылать ответные письма
     */
    public const MAIL_REPLYTO_ADDRESS = 'user@domain.com';

    /**
     * Кому отсылать ответные письма
     */
    public const MAIL_REPLYTO_NAME = 'domain.com';

    /**
     * Протокол безопасности
     */
    public const MAIL_SMTPSECURE = 'ssl';

    /**
     * @param string $name
     *
     * @return array|false|mixed|null|string
     */
    public static function getSetting(string $name)
    {
        return getenv($name) ?? \constant("static::$name") ?? null;
    }

    /**
     * Отправка почты
     *
     * @param array $addresses - массив адресов под рассылку
     * @param string $subject - тема письма
     * @param string $message - тело письма
     * @param array $files - прикрепляемые файлы
     *
     * @return bool
     *
     * @throws \PHPMailer\PHPMailer\Exception
     * @throws \ReflectionException
     */
    public static function mailSend(array $addresses, string $subject, string $message, array $files = []): bool
    {
        $mail = new PHPMailer();

        //$mail->SMTPDebug = 4;

        $reflector = new \ReflectionClass(PHPMailer::class);
        $mailerDir = \dirname($reflector->getFileName());

        $mail->setLanguage(
            static::getSetting('MAIL_LANG'),
            "$mailerDir/language/phpmailer.lang-" . static::getSetting('MAIL_LANG') . '.php'
        );
        $mail->CharSet = static::getSetting('MAIL_CHARSET');
        $mail->isSMTP();
        $mail->Host = static::getSetting('MAIL_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = static::getSetting('MAIL_USERNAME');
        $mail->Password = static::getSetting('MAIL_PASSWORD');
        $mail->SMTPSecure = static::getSetting('MAIL_SMTPSECURE');
        $mail->Port = static::getSetting('MAIL_PORT');

        $mail->From = static::getSetting('MAIL_FROM');
        $mail->FromName = static::getSetting('MAIL_FROM_NAME');

        foreach ($addresses as &$value) {
            $mail->addAddress($value);
        }

        unset($value);

        $mail->addReplyTo(static::getSetting('MAIL_REPLYTO_ADDRESS'), static::getSetting('MAIL_REPLYTO_NAME'));

        $mail->WordWrap = 50;

        foreach ($files as $file) {
            $mail->addAttachment($file);
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;

        if (!$mail->send()) {
            return false;
        }

        return true;
    }
}