<?php

namespace app\models;

use Yii;

class FileSystemAdapter
{
    public static $web_server_login = 'www-data';

    public static function safeReadFile(string $file_path)
    {
        try {
            return file_get_contents($file_path);
        } catch (\Throwable $th) {
            Yii::error("Ошибка чтения файла: $file_path");
            Yii::error($th);
        }
    }

    public static function safeWriteFile(string $file_path, $data)
    {
        if (!file_exists(dirname($file_path))) {
            static::safeCreateDir(dirname($file_path));
        }

        try {
            file_put_contents($file_path, $data);
        } catch (\Throwable $th) {
            Yii::error("Ошибка записи в файл: $file_path");
            Yii::error($th);
        }

        try {
            if (static::isRoot()) {
                chown($file_path, static::$web_server_login);
            }
        } catch (\Throwable $th) {
            Yii::error("Ошибка записи в файл: $file_path");
            Yii::error($th);
        }
    }

    public static function safeCreateDir(string $dir_path)
    {
        if (!file_exists(dirname($dir_path))) {
            static::safeCreateDir(dirname($dir_path));
        }

        try {
            mkdir($dir_path, 0644, false);
        } catch (\Throwable $th) {
            Yii::error("Ошибка создания директории: $dir_path");
            Yii::error($th);
        }

        return true;
    }

    public static function isRoot(): bool
    {
        return 'root' === posix_getlogin();
    }
}
