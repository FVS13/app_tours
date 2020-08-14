<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property string $airline_code Код авиакомпании
 * @property string|null $icao Код самолёта
 * @property string $airplane_model Название модели самолёта
 * @property int $passenger_capacity Вместимость самолёта
 */
class AircraftReference extends ActiveRecord
{
    private static $aircraft_reference_by_id = [];

    public static function tableName()
    {
        return '{{%aircraft_reference}}';
    }

    public static function cacheInit()
    {
        $aircraft_reference = static::find()->all();

        foreach ($aircraft_reference as $aircraft) {
            $airline_code = $aircraft->airline_code;
            $icao_code = $aircraft->icao;

            static::$aircraft_reference_by_id[(string) $airline_code ][(string) $icao_code ] = $aircraft;
        }
    }

    public static function getByAirlineAndIcao(string $airline_code, string $icao_code): ?AircraftReference
    {
        return static::$aircraft_reference_by_id[(string) $airline_code ][(string) $icao_code ] ?? null;
    }
}
