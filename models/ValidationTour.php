<?php

namespace app\models;

/**
 * Валидация данных о рейсах
 */
class ValidationTour
{
    public const DATE_FORMAT = '/^([0-9]{4})-([0-1][0-9])-([0-3][0-9])T([0-2][0-9]):([0-5][0-9]):([0-5][0-9])$/';
    public const PRICE_FORMAT = '/^[0-9]+([.,][0-9]*)?$/';

    private $tour_type = '';
    private $errors = [];

    public function __construct(string $tour_type)
    {
        $this->tour_type = $tour_type;
    }

    public function __get(string $name)
    {
        switch ($name) {
            case 'errors':
                return $this->errors;
                break;
        }
    }

    /**
     * Метод-контроллер, в котором выбирается метод валидации конкретного рейса
     *
     * @param  array   $tour Запись об одном конкретном рейсе.
     * @return boolean       Результат проверки на валидность.
     */
    public function validate(array $tour): bool
    {
        $this->errors = [];

        if (empty($tour['collected_at_gmt']) || empty($tour['validity'])) {
            $this->errors[] = "Отсутствует время сбора ($tour[collected_at_gmt]) или источник ($tour[validity])";
            return false;
        }

        switch ($this->tour_type) {
            case 'Y':
            case 'C':
                return $this->validateFlight($tour);
            case 'BUS':
                return $this->validateTrip($tour);

            default:
                $this->errors[] = 'Неизвестный тип рейса: ' . $this->tour_type;
                return false;
        }
    }

    /**
     * Валидация всей записи об авиа-перелёте
     *
     * @param  array   $flight Сегменты и тарифы.
     * @return boolean         Результат проверки на валидность.
     */
    private function validateFlight(array $flight): bool
    {
        $flight_file = new FlightFile();
        $flight_file->attributes = $flight;

        $res = $flight_file->validate() && empty($flight_file->excluding_errors);

        $this->errors = $flight_file->errors;
        $this->excluding_errors = $flight_file->excluding_errors;

        return $res;
    }

    /**
     * Валидация записи об автобусной поездке
     *
     * @param  array   $trip Вся информация о поездке.
     * @return boolean       Результат проверки на валидность.
     */
    private function validateTrip(array $trip): bool
    {
        $trip_file = new TripFile();
        $trip_file->attributes = $trip;

        $res = $trip_file->validate();

        $this->errors = $trip_file->errors;

        return $res;
    }

    /**
     * Проверка корректности даты.
     *
     * Проверка соответствия заданному формату
     * и проверка существования.
     *
     * @param  string  $date Дата
     * @return boolean       Результат проверки корректности даты
     */
    public static function checkdate(string $date): bool
    {
        if (!preg_match(static::DATE_FORMAT, $date, $date_params)) {
            return false;
        }

        $year   = (int) $date_params[1];
        $month  = (int) $date_params[2];
        $day    = (int) $date_params[3];
        $hour   = (int) $date_params[4];
        $minute = (int) $date_params[5];
        $second = (int) $date_params[6];

        return \checkdate($month, $day, $year)
            && ($hour >= 0 && $hour < 24)
            && ($minute >= 0 && $minute < 60)
            && ($second >= 0 && $second < 60);
    }

    public static function isValidPrice($string): bool
    {
        return (1 === preg_match(static::PRICE_FORMAT, $string));
    }
}
