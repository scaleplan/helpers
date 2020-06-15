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

    public const DIRECTORY_MODE = 0775;

    /**
     * Сохранить массив в csv-файл
     *
     * @param array $data - массив данных
     * @param string $filePath - путь к директории файла
     * @param string $fileName - имя файла для сохраниения
     *
     * @throws FileReturnedException
     */
    public static function returnASCSVFile(array $data, string $filePath, string $fileName = 'tmp.csv') : void
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
    public static function returnFile(string $filePath) : void
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
     * @param array $file
     * @param string $uploadPath
     * @param int $index
     *
     * @return array|null
     *
     * @throws Exceptions\EnvNotFoundException
     * @throws FileSaveException
     * @throws \Throwable
     */
    public static function saveFile(array &$file, string &$uploadPath, int &$index = -1) : ?array
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
                    . 'указанное в HTML-форме.', 413);

            case UPLOAD_ERR_PARTIAL:
                throw new FileSaveException('Загружаемый файл был получен только частично.', 400);

            case UPLOAD_ERR_NO_TMP_DIR:
                throw new FileSaveException('Отсутствует временная папка.', 500);

            case UPLOAD_ERR_CANT_WRITE:
                throw new FileSaveException("Не удалось записать файл $fn на диск.", 500);

            case UPLOAD_ERR_EXTENSION:
                throw new FileSaveException('PHP-расширение остановило загрузку файла.', 500);
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

        if (!file_exists($tn)) {
            throw new FileSaveException("Временный файл $tn не найден.");
        }

        if (!is_uploaded_file($tn)) {
            throw new FileSaveException("Не удалось записать файл $fn на диск.", 500);
        }

        if (@filesize($tn) > (1048576 * $fileMaxSizeMb)) {
            throw new FileSaveException(
                "Размер загружаемого файла не может быть больше значения $fileMaxSizeMb мегабайт.", 413
            );
        }

        if (!static::validateFileExt($ext)) {
            throw new FileSaveException("Неподдерживаемое расширение '$ext'.", 415);
        }

//            if (!($validExt = static::validateFileMimeType($tn))) {
//                throw new FileSaveException('Неподдерживаемый тип файла', 415);
//            }
//
//            if ($validExt !== $ext) {
//                $ext = $validExt;
//            }

        $newName = "$newName.$ext";
        $path = "$uploadPath/$newName";
        if (!copy($tn, $path)) {
            throw new FileSaveException("Файл $fn не был корректно сохранен.", 500);
        }

        $path = getenv('FILES_URL_PREFIX') . strtr(
                $path,
                [
                    get_required_env('BUNDLE_PATH')          => '',
                    get_required_env('FILES_DIRECTORY_PATH') => '',
                ]
            );
        return ['name' => $fn, 'path' => $path];
    }

    /**
     * Функция загрузки массива файлов на сервер
     *
     * @param array $files - массив файлов, которые прислала форма и мета-информация о них
     *
     * @return array
     *
     * @throws Exceptions\EnvNotFoundException
     * @throws Exceptions\HelperException
     * @throws FileSaveException
     * @throws FileUploadException
     * @throws \Throwable
     */
    public static function saveFiles(array $files) : array
    {
        $result = [];
        foreach ($files as $field => &$file) {
            $uploadPath = static::getFilePath($field);
            if (!is_dir($uploadPath)
                && !mkdir($uploadPath, static::DIRECTORY_MODE, true)
                && chmod($uploadPath, static::DIRECTORY_MODE)
            ) {
                throw new FileSaveException('Не удалось создать директорию сохранения.', 500);
            }

            if (\is_array($file['name'])) {
                foreach ($file['name'] as $index => &$fn) {
                    if ($moveFile = static::saveFile($file, $uploadPath, $index)) {
                        $result[$field][] = $moveFile;
                    }
                }

                unset($fn);
            } elseif ($moveFile = static::saveFile($file, $uploadPath)) {
                $result[$field] = $moveFile;
            }
        }

        unset($file);
        return $result;
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
    public static function validateFileExt(string &$extName) : bool
    {
        if (empty(Helper::getConf(get_required_env('EXTS_CONFIG_NAME'))[strtolower($extName)])) {
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
    public static function validateFileMimeType(string &$filePath) : ?string
    {
        if (!file_exists($filePath)) {
            throw new FileValidationException("Файл $filePath не существует.");
        }

        if (empty($validExt = Helper::getConf(get_required_env('MIMES_CONFIG_NAME'))[mime_content_type($filePath)])) {
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
    public static function getFilePath(string $fileKind) : string
    {
        $locations = Helper::getConf(get_required_env('FILE_LOCATIONS_CONFIG_NAME'), false);
        $location = null;
        if (!\is_array($locations) || empty($location = $locations[$fileKind] ?? null)) {
            foreach (array_keys($locations) as $field) {
                if (@preg_match($field, $fileKind)) {
                    $location = $locations[$field];
                    break;
                }
            }
        }

        if (!$location) {
            throw new FileUploadException("Для поля $fileKind не задан путь сохранения.");
        }

        return get_required_env('BUNDLE_PATH')
            . get_required_env('FILES_DIRECTORY_PATH')
            . $location;
    }

    /**
     * Найти все файлы в каталоге, включая вложенные директории
     *
     * @param string $dirPath - путь к каталогу
     * @param array|null $extensions - фильтр по расширению файла
     *
     * @return array
     */
    public static function getRecursivePaths(string $dirPath, array $extensions = null) : array
    {
        if (!\is_dir($dirPath)) {
            return [];
        }

        $dirPath = rtrim($dirPath, '/\ ');
        $paths = \scandir($dirPath, SCANDIR_SORT_NONE);
        unset($paths[0], $paths[1]);
        $result = [];

        foreach ($paths as $path) {
            $path = "$dirPath/$path";
            if (!\is_dir($path)) {
                $result[] = $path;
                continue;
            }

            $result += array_map(static function ($item) use ($path) {
                return "$path/$item";
            }, static::getRecursivePaths($path));
        }

        if (null !== $extensions) {
            $result = preg_grep('~\.(' . implode('|', $extensions) . ')$~', $result);
        }

        return $result;
    }
}
