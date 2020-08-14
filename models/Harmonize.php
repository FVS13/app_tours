<?php

namespace app\models;

// TODO Перенести использование гармонизации кавычек и количества свободных мест на этап экспорта
class Harmonize
{
    /**
     * Пересчёт из местного времени в московское
     *
     * @param string $datetime Местное время в формате строки
     * @param string $gmt Часовой пояс в виде строки (например "+3")
     * @return string Московское время
     */
    // TODO добавить модульные тесты к этому методу
    public static function datetimeMsk(string $datetime, string $gmt): string
    {
        // +5:30
        preg_match('/^([+-])?([0-9]{1,2})(:([0-9]{1,2}))?$/', $gmt, $gmt_time);
        $sign = $gmt_time[1] ?? '+';
        $hour = $gmt_time[2];
        $minute = $gmt_time[4] ?? 0;

        $abs_seconds_diff = $hour * 60 * 60 + $minute * 60;
        $seconds_diff = (int) ($sign . '1') * $abs_seconds_diff;

        $datetime_msk_time = strtotime($datetime) - $seconds_diff + 60 * 60 * 3;
        $datetime_msk = \str_replace(' ', 'T', date('Y-m-d H:i:s', $datetime_msk_time));

        return $datetime_msk;
    }

    /**
     * Корректировка количества свободных мест
     *
     * @param string $seats_count Входное количество свободных мест
     * @param string $default Замена для некорректных значений
     * @param integer $max Максимальное количество мест
     * @return string Корректное количество свободных мест
     */
    public static function seatsCount(string $seats_count, string $default): string
    {
        // Цифра, или индикативная величина с плюсом (например "9+")
        if (
            ! preg_match('/^[0-9]+\+?$/', $seats_count)
            || $seats_count < 1
        ) {
            $seats_count = $default;
        }

        return $seats_count;
    }

    /**
     * Замена кавычек на неэкранируемые
     *
     * @param string $string
     * @return string
     */
    public static function quotes(string $string): string
    {
        return str_replace('"', "'", $string);
    }
}
