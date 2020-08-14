<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property string $code Код авиакомпании
 * @property string $name Название авиакомпании
 */
class Airlines extends ActiveRecord
{
    /**
     * @var array ['code' => 'name']
     */
    private static $airlines = [];
    private static $airlines_by_id = [];

    public function scenarios()
    {
        return [
            'default' => ['name', 'code'],
        ];
    }

    public static function cacheInit()
    {
        if (!empty(static::$airlines)) {
            return;
        }

        $curr_airlines = static::find()->all();

        foreach ($curr_airlines as $airline) {
            static::$airlines[ $airline->code ] = [
                'id' => $airline->id,
                'name' => $airline->name,
                'main_code' => $airline->code,
            ];

            for ($i = 1; $i <= 3; $i++) {
                if (empty($airline->{"alt_code$i"})) {
                    continue;
                }

                static::$airlines[ (string) $airline->{"alt_code$i"} ] = [
                    'id' => $airline->id,
                    'name' => $airline->name,
                    'main_code' => $airline->code,
                ];
            }

            static::$airlines_by_id[ $airline->id ] = $airline;
        }
    }

    /**
     * Возвращает ID в базе данных
     *
     * @param array<string,string> $attributes ['name' => 'название авиакомпании', 'code' => 'код авиакомпании']
     * @return integer
     */
    public static function findOrCreateId(array $attributes): int
    {
        $code = $attributes['code'];
        $name = $attributes['name'] ?? '';

        if (!array_key_exists($code, static::$airlines)) {
            $current_airline = new static();
            $current_airline->attributes = $attributes;

            $current_airline->save();

            static::$airlines[ $code ] = [
                'id' => $current_airline->id,
                'name' => $name,
                'main_code' => $current_airline->code,
            ];
        }

        if (empty(static::$airlines[ $code ]['name']) && !empty($name)) {
            static::$airlines[ $code ]['name'] = $name;
        }

        return static::$airlines[ $code ]['id'];
    }

    /**
     * Возвращает основной код авиакомпании по любому существующему
     *
     * @param string $code
     * @return string
     */
    public static function getMainCode(string $code): string
    {
        return static::$airlines[ (string) $code ]['main_code'] ?? '';
    }

    public static function getById(int $id): ?Airlines
    {
        return static::$airlines_by_id[ $id ] ?? null;
    }
}
