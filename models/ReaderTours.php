<?php

namespace app\models;

use app\exceptions\ParseTourFileException;

class ReaderTours
{
    public const READ_FILE_PREFIX = '_';

    private $root = '';
    private $count_read_tours = 0;
    private $current_filepath = '';
    private $is_enable_marker = true;
    private $last_invalid_tours = [];

    /**
     * Конструктор класса
     *
     * @param string  $root             Корневая директория
     * @param boolean $is_enable_marker Надо ли помечать файлы, как прочитанные
     */
    public function __construct(string $root, bool $is_enable_marker = true)
    {
        $this->root = realpath($root);

        if (!$this->root) {
            throw new \Exception('Передан некорректный путь к директории');
        }

        if (!is_dir($this->root)) {
            throw new \Exception('По указанному пути находится не директория');
        }

        if (!is_readable($this->root)) {
            throw new \Exception('Указанная директория не доступна для чтения');
        }

        $this->is_enable_marker = $is_enable_marker;

        if ($this->is_enable_marker && !is_writable($this->root)) {
            throw new \Exception('Указанная директория не доступна для записи');
        }
    }

    public function __get(string $name)
    {
        switch ($name) {
            case 'current_filepath':
                return $this->current_filepath;
            case 'count_read_tours':
                return $this->count_read_tours;
            case 'last_invalid_tours':
                return $this->last_invalid_tours;
        }

        return null;
    }

    public function resetLastInvalidTours()
    {
        $this->last_invalid_tours = [];
    }

    /**
     * Возвращает абсолютные пути ко всем файлам в указанной директории, и во всех вложенных
     *
     * @param  string $current_dir Абсолютный путь к директории, из которой будут прочитаны файлы
     * @return \Generator
     */
    private static function readFiles(string $current_dir): \Generator
    {
        clearstatcache(true, $current_dir);
        $dirs_list = scandir($current_dir);

        foreach ($dirs_list as $dir_name) {
            if ('.' === $dir_name || '..' === $dir_name) {
                continue;
            }

            $sub_dir = $current_dir . '/' . $dir_name;

            if (is_file($sub_dir)) {
                yield $sub_dir;
            }

            if (is_dir($sub_dir)) {
                $dirs = static::readFiles($sub_dir);

                foreach ($dirs as $dir) {
                    yield $dir;
                }
            }
        }
    }

    /**
     * Получает все записи о рейсах
     *
     * Возвращает объект типа генератор,
     * который возвращает все записи о рейсах по одной
     *
     * @return \Generator
     */
    public function getAllToursFiles(): \Generator
    {
        $files = static::readFiles($this->root);

        foreach ($files as $path_to_json) {
            if (
                !is_file($path_to_json)
                || static::isFileRead($path_to_json)
            ) {
                continue;
            }

            $file_content = file_get_contents($path_to_json);

            if ($this->is_enable_marker) {
                static::markFileAsRead($path_to_json);
            }

            $this->current_filepath = $path_to_json;

            yield $file_content;
        }

        $this->current_filepath = '';
    }

    /**
     * Помечает файл, как прочитанный
     *
     * @param  string  $path_to_json Абсолютный путь к файлу
     * @return boolean               true, в случае успешного переименования, и false в случае ошибки
     */
    private static function markFileAsRead(string $path_to_json): bool
    {
        $dirname  = dirname($path_to_json);
        $filename = basename($path_to_json);
        $new_filepath = $dirname . '/' . static::READ_FILE_PREFIX . $filename;

        try {
            rename($path_to_json, $new_filepath);
        } catch (\Throwable $th) {
            \Yii::error("Ошибка переименования файла: '$path_to_json' -> '$new_filepath'");
            return false;
        }

        return true;
    }

    /**
     * Проверяет, был ли уже прочитан файл
     *
     * @param  string $path_to_json Абсолютный путь к файлу
     * @return boolean
     */
    private static function isFileRead(string $path_to_json): bool
    {
        $reg_exp = \preg_quote(static::READ_FILE_PREFIX);

        return preg_match("/^$reg_exp/", basename($path_to_json));
    }

    /**
     * Получает записи о рейсах из данного файла
     *
     * Возвращает массив валидных рейсов
     * Очищает last_invalid_tours перед чтением нового файла
     *
     * @param  string $file_content Содержимое файла
     * @param  string $tour_type Тип рейса (автобусный, самолётный)
     * @return array
     */
    public function getToursFromFile(string $file_content): array
    {
        $this->resetLastInvalidTours();
        $tours = json_decode($file_content, true);

        if (!is_array($tours)) {
            throw new ParseTourFileException("Обнаружен файл с невалидным JSON: \"$this->current_filepath\"");
        }

        $this->count_read_tours += count($tours);

        return $tours;
    }

    /**
     * @param string $path Полный путь к файлу
     * @return string[]
     */
    public static function getInfoFromPath(string $path): array
    {
        $tour_info['validity'] = basename(dirname($path, 3));
        $tour_info['route_code'] = basename(dirname($path, 2));
        $tour_info['tour_type'] = basename(dirname($path));
        $tour_info['date'] = basename($path, '.json');

        return $tour_info;
    }

    public static function getMaxCollectedGmt(array $tours): ?string
    {
        $max_collected_at_gmt = null;

        foreach ($tours as $tour) {
            $current_collected_at_gmt = $tour['collected_at_gmt'];

            if (
                !isset($max_collected_at_gmt)
                || $current_collected_at_gmt > $max_collected_at_gmt
            ) {
                $max_collected_at_gmt = $current_collected_at_gmt;
            }
        }

        return $max_collected_at_gmt;
    }
}
