<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property Citys $city Город, к которому привязан аэропорт
 * @property Airports $airport Аэропорт, который привязан к городу
 * @property Airports $airportFromCache Аэропорт, который привязан к городу, взятый из кеша
 * @property integer $tracked_city_id
 * @property integer $binded_airport_id
 * @property integer $is_tracked Отслеживается ли авиасообщение по привязанному аэропорту
 * @property integer $distance_to_airport Расстояние от города, до привязанного аэропорта
 */
class BindedAirports extends ActiveRecord
{
    public function getCity()
    {
        return $this->hasOne(Citys::class, ['id' => 'tracked_city_id']);
    }

    public function getAirport()
    {
        return $this->hasOne(Airports::class, ['id' => 'binded_airport_id']);
    }

    public function getAirportFromCache(): Airports
    {
        return Airports::getCachedObject($this->binded_airport_id);
    }

    public function rules()
    {
        return [
            ['tracked_city_id', 'required', 'message' => 'Не указан отслеживаемый город'],
            ['tracked_city_id', 'integer', 'message' => 'Некорректный id отслеживаемого города'],
            ['binded_airport_id', 'required', 'message' => 'Не указан аэропорт'],
            ['binded_airport_id', 'integer', 'message' => 'Некорректный id аэропорта'],
            ['distance_to_airport', 'required', 'message' => 'Не указано расстояние до аэропорта'],
            [
                'distance_to_airport',
                'integer',
                'min' => 0,
                'max' => 13000,
                'message' => 'Некорректное расстояние до аэропорта',
                'tooSmall' => 'Расстояние не может быть отрицательным',
                'tooBig' => 'Расстояние не может быть больше радиуса Земли (13 000 км)',
            ],
            ['is_tracked', 'boolean'],
        ];
    }
}
