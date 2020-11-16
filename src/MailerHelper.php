<?php

namespace Scaleplan\Helpers;

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Хэлпер отправки писем
 *
 * Class MailerHelper
 */
class MailerHelper
{
    /* Настроки PHPMailer */

    /**
     * Язык писем
     */
    public const DEFAULT_MAIL_LANG = 'ru';

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
     * Отправка почты
     *
     * @param array $addresses - массив адресов под рассылку
     * @param string $subject - тема письма
     * @param string $message - тело письма
     * @param string|null $mailLang - язык письма
     * @param array $files - прикрепляемые файлы
     *
     * @return bool
     *
     * @throws Exceptions\EnvNotFoundException
     * @throws \PHPMailer\PHPMailer\Exception
     * @throws \ReflectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ContainerTypeNotSupportingException
     * @throws \Scaleplan\DependencyInjection\Exceptions\DependencyInjectionException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ParameterMustBeInterfaceNameOrClassNameException
     * @throws \Scaleplan\DependencyInjection\Exceptions\ReturnTypeMustImplementsInterfaceException
     */
    public static function mailSend(array $addresses, string $subject, string $message, string $mailLang = null, array $files = []): bool
    {
        $mail = new PHPMailer();

        //$mail->SMTPDebug = 4;

        $reflector = new \ReflectionClass(PHPMailer::class);
        $mailerDir = \dirname($reflector->getFileName());
        $mailLang = $mailLang ?? get_required_env('DEFAULT_MAIL_LANG');

        $mail->setLanguage($mailLang, "$mailerDir/language/phpmailer.lang-$mailLang.php");
        $mail->CharSet = get_required_env('MAIL_CHARSET');
        $mail->isSMTP();
        $mail->Host = get_required_env('MAIL_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = get_required_env('MAIL_USERNAME');
        $mail->Password = get_required_env('MAIL_PASSWORD');
        $mail->SMTPSecure = get_required_env('MAIL_SMTPSECURE');
        $mail->Port = get_required_env('MAIL_PORT');

        $mail->From = get_required_env('MAIL_FROM');
        $mail->FromName = get_required_env('MAIL_FROM_NAME');

        foreach ($addresses as &$value) {
            $mail->addAddress($value);
        }

        unset($value);

        $mail->addReplyTo(get_required_env('MAIL_REPLYTO_ADDRESS'), get_required_env('MAIL_REPLYTO_NAME'));

        $mail->WordWrap = 50;

        foreach ($files as $file) {
            $mail->addAttachment($file);
        }

        $mail->isHTML();
        $mail->Subject = $subject;
        $mail->Body = $message;

        if (!$mail->send()) {
            return false;
        }

        return true;
    }
}
