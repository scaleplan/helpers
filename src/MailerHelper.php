<?php

namespace Scaleplan\Helpers;

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Хэлпер отправки писем
 *
 * Class MailerHelper
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
    public const MAIL_USERNAME = 'admin@qooiz.me';

    /**
     * Пароль для авторизации на SMTP-сервере
     */
    public const MAIL_PASSWORD = '81lj54ewv9';

    /**
     * Порт для подключения к SMTP-серверу
     */
    public const MAIL_PORT = 465;

    /**
     * Обратный адрес писем
     */
    public const MAIL_FROM = 'admin@qooiz.me';

    /**
     * Имя отправителя
     */
    public const MAIL_FROM_NAME = 'qooiz.me';

    /**
     * Куда присылать ответные письма
     */
    public const MAIL_REPLYTO_ADDRESS = 'admin@qooiz.me';

    /**
     * Кому отсылать ответные письма
     */
    public const MAIL_REPLYTO_NAME = 'qooiz.me';

    /**
     * Протокол безопасности
     */
    public const MAIL_SMTPSECURE = 'ssl';

    /**
     * @param string $name
     *
     * @return array|false|mixed|null|string
     */
    public function getSetting(string $name)
    {
        return getenv($name) ?? constant("static::$name") ?? null;
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
            self::MAIL_LANG,
            "$mailerDir/language/phpmailer.lang-" . self::MAIL_LANG . '.php'
        );
        $mail->CharSet = self::MAIL_CHARSET;
        $mail->isSMTP();
        $mail->Host = self::MAIL_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = self::MAIL_USERNAME;
        $mail->Password = self::MAIL_PASSWORD;
        $mail->SMTPSecure = self::MAIL_SMTPSECURE;
        $mail->Port = self::MAIL_PORT;

        $mail->From = self::MAIL_FROM;
        $mail->FromName = self::MAIL_FROM_NAME;

        foreach ($addresses as &$value) {
            $mail->addAddress($value);
        }

        unset($value);

        $mail->addReplyTo(self::MAIL_REPLYTO_ADDRESS, self::MAIL_REPLYTO_NAME);

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