<?php

namespace app\models;

use yii\base\Model;
use yii\db\Query;

class FlightFile extends Model
{
    public $collected_at_gmt;
    public $validity;
    public $fares;
    public $segment_1;
    public $segment_2;

    private static $excluded_airlines = null;
    public $excluding_errors = [];

    public function scenarios()
    {
        return [
            'default' => ['collected_at_gmt', 'validity', 'fares', 'segment_1', 'segment_2'],
        ];
    }

    public function rules()
    {
        return [
            ['collected_at_gmt', 'required', 'message' => 'Не указано время сбора'],
            ['validity', 'required', 'message' => 'Не указан источник'],
            ['fares', 'required', 'message' => 'Нет тарифов'],
            ['segment_1', 'required', 'message' => 'Нет первого сегмента'],

            ['segment_1', 'validateFlightSegment'],
            ['segment_2', 'validateFlightSegment'],
            ['fares', 'validateFlightFares'],

            ['segment_1', 'isExcludedAirline'],
            ['segment_2', 'isExcludedAirline'],
        ];
    }

    public function validateFlightSegment(string $attribute)
    {
        if (empty($this->$attribute['flight_num'])) {
            $this->addError($attribute, 'Не указан номер рейса');
        }

        if (empty($this->$attribute['departure_time'])) {
            $this->addError($attribute, 'Не указано время отправления');
        }

        if (empty($this->$attribute['arrival_time'])) {
            $this->addError($attribute, 'Не указано время прибытия');
        }

        if (empty($this->$attribute['departure_city']['name'])) {
            $this->addError($attribute, 'Не указан код авиакомпании');
        }

        if (empty($this->$attribute['arrival_city']['name'])) {
            $this->addError($attribute, 'Не указан код авиакомпании');
        }

        if (empty($this->$attribute['marketing_carrier']['code'])) {
            $this->addError($attribute, 'Не указан код авиакомпании');
        }

        if (!ValidationTour::checkdate($this->$attribute['departure_time'])) {
            $this->addError($attribute, 'Указанной даты отправления не существует');
        }

        if (!ValidationTour::checkdate($this->$attribute['arrival_time'])) {
            $this->addError($attribute, 'Указанной даты прибытия не существует');
        }

        if ('segment_1' === $attribute) {
            return;
        }

        if (
            empty($this->{'segment_1'}['arrival_city']['name'])
            && empty($this->$attribute['departure_city']['name'])
        ) {
            $this->addError('segment_1', 'Не указан город прибытия');
            $this->addError($attribute, 'Не указан город отправления');
        }
    }

    public function validateFlightFares(string $attribute)
    {
        foreach ($this->$attribute as $tariff) {
            if (empty($tariff['price'])) {
                $this->addError($attribute, 'Не указана цена одного из тарифов');
                continue;
            }

            if (!ValidationTour::isValidPrice($tariff['price'])) {
                $this->addError($attribute, 'Неверный формат цены одного из тарифов');
            }
        }
    }

    public function isExcludedAirline(string $attribute)
    {
        $code = $this->$attribute['marketing_carrier']['code'] ?? null;
        $name = $this->$attribute['marketing_carrier']['name'] ?? null;

        if (!empty($code) && static::existsAirlineInExcludedList($code, $name)) {
            $this->excluding_errors[] = [$attribute, "Авиакомпания входит в список исключённых: $code"];
        }
    }

    private static function existsAirlineInExcludedList(string $code, string $name = null): bool
    {
        if (null === static::$excluded_airlines) {
            static::$excluded_airlines = (new Query())
                ->select(['name', 'code'])
                ->from('{{%excluded_airlines}}')
                ->all();

            if (
                empty(static::$excluded_airlines)
                && count(static::$excluded_airlines) > 0
            ) {
                return false;
            }

            static::$excluded_airlines = array_column(static::$excluded_airlines, 'name', 'code');
        }

        return array_key_exists($code, static::$excluded_airlines)
            || !empty($name) && array_search($name, static::$excluded_airlines);
    }
}
