<?php

namespace Scaleplan\Helpers;

use Scaleplan\Helpers\Exceptions\FileReturnedException;
use Scaleplan\Helpers\Exceptions\FileSaveException;
use Scaleplan\Helpers\Exceptions\FileUploadException;
use Scaleplan\Helpers\Exceptions\FileValidationException;

/**
 * Хэлпер манипуляций над файлами
 *
 * Class FileHelper
 *
 * @package Scaleplan\Helpers
 */
class FileHelper
{
    /**
     * Максимальный размер загружаемых файлов (в мегабатах)
     */
    public const FILE_UPLOAD_MAX_SIZE = 300;

    public const FREAD_DEFAULT_LENGTH = 1024;

    public const FILES_DIRECTORY_PATH = '/files';

    public const FILE_LOCATIONS_FILE_PATH = 'paths';

    /**
     * Сохранить массив в csv-файл
     *
     * @param array $data - массив данных
     * @param string $filePath - путь к директории файла
     * @param string $fileName - имя файла для сохраниения
     *
     * @throws FileReturnedException
     */
    public static function returnASCSVFile(array $data, string $filePath, string $fileName = 'tmp.csv'): void
    {
        $fileName = $filePath . '/' . $fileName;

        static::returnFile($fileName);

        $fp = fopen($fileName, 'wb');
        //fputs($fp, '\xEF\xBB\xBF');
        foreach ($data as $fields) {
            fputcsv($fp, $fields);
        }

        fclose($fp);

        static::returnFile($fileName);

        throw new FileReturnedException();
    }

    /**
     * Вернуть файл пользователю
     *
     * @param string $filePath - путь к файлу
     */
    public static function returnFile(string $filePath): void
    {
        if (file_exists($filePath)) {
            // сбрасываем буфер вывода PHP, чтобы избежать переполнения памяти выделенной под скрипт
            // если этого не сделать файл будет читаться в память полностью!
            if (ob_get_level()) {
                ob_end_clean();
            }
            // заставляем браузер показать окно сохранения файла
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename($filePath));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            // читаем файл и отправляем его пользователю
            if ($fd = fopen($filePath, 'rb')) {
                while (!feof($fd)) {
                    print fread($fd, static::FREAD_DEFAULT_LENGTH);
                }

                fclose($fd);
            }

            exit;
        }
    }

    /**
     * Функция загрузки массива файлов на сервер
     *
     * @param array $files - массив файлов, которые прислала форма и мета-информация о них
     *
     * @return array
     *
     * @throws Exceptions\HelperException
     * @throws FileUploadException
     */
    public static function saveFiles(array $files): array
    {
        $saveFile = static function (array &$file, string &$uploadPath, int &$index = -1): ?array
        {
            if ($index >= 0) {
                $fn = &$file['name'][$index];
                $tn = &$file['tmp_name'][$index];
                $fe = &$file['error'][$index];
            } else {
                $fn = &$file['name'];
                $tn = &$file['tmp_name'];
                $fe = &$file['error'];
            }

            switch ($fe) {
                case UPLOAD_ERR_OK:
                    break;

                case UPLOAD_ERR_NO_FILE:
                    return null;

                case UPLOAD_ERR_INI_SIZE:
                    throw new FileSaveException(
                        'Размер принятого файла превысил максимально допустимый '
                        . 'размер, который задан директивой upload_max_filesize конфигурационного файла php.ini',
                        413
                    );

                case UPLOAD_ERR_FORM_SIZE:
                    throw new FileSaveException('Размер загружаемого файла превысил значение MAX_FILE_SIZE, '
                    . 'указанное в HTML-форме', 413);

                case UPLOAD_ERR_PARTIAL:
                    throw new FileSaveException('Загружаемый файл был получен только частично', 400);

                case UPLOAD_ERR_NO_TMP_DIR:
                    throw new FileSaveException('Отсутствует временная папка', 500);

                case UPLOAD_ERR_CANT_WRITE:
                    throw new FileSaveException('Не удалось записать файл на диск', 500);

                case UPLOAD_ERR_EXTENSION:
                    throw new FileSaveException('PHP-расширение остановило загрузку файла', 500);
            }

            $nameArray = explode('.', $fn);
            $ext = strtolower(end($nameArray));
            $newName = preg_replace(
                '/[\s,\/:;\?!*&^%#@$|<>~`]/',
                '',
                str_replace(
                    ' ',
                    '_',
                    str_replace($ext, '', $fn) . microtime(true)
                )
            );

            $fileMaxSizeMb = (int)(get_env('FILE_UPLOAD_MAX_SIZE') ?? static::FILE_UPLOAD_MAX_SIZE);

            if (!is_uploaded_file($tn)) {
                unlink($tn);
                throw new FileSaveException('Не удалось записать файл на диск', 500);
            }

            if (filesize($tn) > (1048576 * (int) $fileMaxSizeMb)) {
                unlink($tn);
                throw new FileSaveException(
                    "Размер загружаемого файла не может быть больше значения $fileMaxSizeMb мегабайт).", 413
                );
            }

            if (!($validExt = static::validateFileMimeType($tn))) {
                unlink($tn);
                throw new FileSaveException('Неподдерживаемый тип файла', 415);
            }

            if ($validExt !== $ext) {
                $ext = $validExt;
            }

            $newName = "$newName.$ext";
            $path = $uploadPath . $newName;
            if (!move_uploaded_file($tn, $path)) {
                throw new FileSaveException("Файл $fn не был корректно сохранен", 500);
            }

            $path = strtr($path, [$_SERVER['DOCUMENT_ROOT'] => '', static::getFilesDirectoryPath() => '']);
            return ['name' => $fn, 'path' => $path];
        };

        $result = [];
        foreach ($files as $field => &$file) {
            $filePath = static::getFilePath($field);
            $uploadPath = $_SERVER['DOCUMENT_ROOT'] . $filePath . '/';
            if (\is_array($file['name'])) {
                foreach ($file['name'] as $index => &$fn) {
                    if ($moveFile = $saveFile($file, $uploadPath, $index)) {
                        $result[$field][] = $moveFile;
                    }
                }

                unset($fn);
            } elseif ($moveFile = $saveFile($file, $uploadPath)) {
                $result[$field][0] = $moveFile;
            }
        }

        unset($file);
        return $result;
    }

    /**
     * @return string
     */
    public static function getFilesDirectoryPath() : string
    {
        return get_env('FILES_DIRECTORY_PATH') ?? static::FILES_DIRECTORY_PATH;
    }

    /**
     * @return string
     */
    public static function getLocationsFilePath() : string
    {
        return get_env('FILE_LOCATIONS_FILE_PATH') ?? static::FILE_LOCATIONS_FILE_PATH;
    }

    /**
     * Проверка расширения файла на возможность загрузки
     *
     * @param $extName - расширение файла
     *
     * @return bool
     *
     * @throws Exceptions\HelperException
     */
    public static function validateFileExt(string &$extName): bool
    {
        if (empty(Helper::getConf('exts')[strtolower($extName)])) {
            return false;
        }

        return true;
    }

    /**
     * Проверка mime-типа файла
     *
     * @param string $filePath - путь к файлу
     *
     * @return null|string
     *
     * @throws Exceptions\HelperException
     * @throws FileValidationException
     */
    public static function validateFileMimeType(string &$filePath): ?string
    {
        if (!file_exists($filePath)) {
            throw new FileValidationException("Файл $filePath не существует");
        }

        if (empty($validExt = Helper::getConf('mimes')[mime_content_type($filePath)])) {
            return null;
        }

        return $validExt;
    }

    /**
     * Возвращает путь к директории с заданным видом файлов
     *
     * @param string $fileKind - вид файлов
     *
     * @return string
     *
     * @throws Exceptions\HelperException
     * @throws FileUploadException
     */
    public static function getFilePath(string $fileKind): string
    {
        $locations = Helper::getConf(static::getLocationsFilePath());
        if (!\is_array($locations) || empty($locations[$fileKind])) {
            throw new FileUploadException("Для поля $fileKind не задан путь сохранения");
        }

        return $locations[$fileKind];
    }
}
