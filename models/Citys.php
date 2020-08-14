<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property BindedAirports $bindedAirport Привязка к аэропорту, по которому отслеживается город
 * @property Citys|null $bindedAirportLocationFromCache Город, по которому отслеживается авиасообщение с данным
 * @property Airports $airport Аэропорт, который находится в этом городе
 * @property AltCitysNames[] $altNames Дополнительные имена города
 * @property integer $id
 * @property string $name Название города
 * @property string $time_zone_gmt Часовой пояс в формате (+|-)?ЧЧ(:ММ)?
 * @property integer $is_tracked Отслеживается ли город
 * @property string $toponymic_analogue
 */
class Citys extends ActiveRecord
{
    private static $city_names_ids = [];
    private static $city_codes_ids = [];
    private static $citys_by_id = [];

    public function scenarios()
    {
        return [
            'default' => ['id', 'name', 'toponymic_analogue', 'time_zone_gmt'],
            'add_new_airport' => ['name', 'toponymic_analogue', 'time_zone_gmt'],
        ];
    }

    public function rules()
    {
        return [
            ['id', 'integer', 'min' => 1, 'message' => 'Некорректный id города'],
            ['name', 'trim'],
            ['name', 'required', 'message' => 'Не указано название города'],
            [
                'name',
                'string',
                'max' => 30,
                'tooLong' => 'Имя города должо быть не больше 30 символов с пробелами'
            ],
            ['name', 'isUniqueName'],
            [
                'toponymic_analogue',
                'string',
                'max' => 40,
                'tooLong' => 'Топонимический аналог должен быть не больше 40 символов с пробелами'
            ],
            ['city_code', 'trim'],
            ['city_code', 'required', 'message' => 'Не указан код города'],
            [
                'city_code',
                'string',
                'max' => 11,
                'tooLong' => 'Код города должен быть не больше 11 символов с пробелами'
            ],
            ['city_code', 'isUniqueCityCode'],
            ['time_zone_gmt', 'trim'],
            ['time_zone_gmt', 'required', 'message' => 'Не указан часовой пояс'],
            [
                'time_zone_gmt',
                'number',
                'numberPattern' => '/^[-+]?((1([0-2])(:00)?)|(0?([0-9])(:[0-5][0-9])?))$/',
                'message' => 'Некорректный часовой пояс'
            ],
        ];
    }

    public function isUniqueCityCode(string $attribute)
    {
        $another_city = static::find()
            ->where([
                'and',
                ['<>', 'id', $this->id],
                ['city_code' => $this->$attribute]
            ])
            ->one();

        if (!empty($another_city)) {
            $this->addError($attribute, "Город с таким кодом уже существует (id: $another_city->id)");
        }
    }

    public function isUniqueName(string $attribute)
    {
        $another_city = static::find()
            ->where([
                'and',
                ['<>', 'id', $this->id],
                ['name' => $this->$attribute],
            ])
            ->one();

        if (!empty($another_city)) {
            $this->addError($attribute, "Город с таким названием уже существует (id: $another_city->id)");
        }

        $exists_alt_name = AltCitysNames::find()
            ->where(['alt_name' => $this->$attribute])
            ->exists();

        if ($exists_alt_name) {
            $this->addError($attribute, 'Уже есть альтернативное название "' . $this->$attribute . '"');
        }
    }

    public function getBindedAirport()
    {
        return $this->hasOne(BindedAirports::class, ['tracked_city_id' => 'id']);
    }

    public function getAltNames()
    {
        return $this->hasMany(AltCitysNames::class, ['city' => 'id']);
    }

    public function getAltNamesAsArray()
    {
        $alt_names_array = [];

        foreach ($this->altNames as $alt_name_obj) {
            $alt_names_array[] = $alt_name_obj->alt_name;
        }

        return $alt_names_array;
    }

    public function getAirport()
    {
        return $this->hasOne(Airports::class, ['location' => 'id']);
    }

    public function getBindedAirportLocationFromCache(): ?Citys
    {
        return $this->bindedAirport->airportFromCache->locationCityFromCache ?? null;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if (
                0 !== $this->time_zone_gmt
                && '+' !== $this->time_zone_gmt[0]
                && '-' !== $this->time_zone_gmt[0]
            ) {
                $this->time_zone_gmt = '+' . $this->time_zone_gmt;
            }

            return true;
        }

        return false;
    }

    /**
     * Список городов в процессе работы программы меняться не должен,
     * поэтому их нужно кешировать
     *
     * @return void
     */
    public static function cacheInit()
    {
        $citys = static::find()->with('airport', 'bindedAirport')->all();
        $alt_names = AltCitysNames::find()->all();

        foreach ($citys as $city) {
            static::$city_names_ids[ $city->name ] = $city->id;
        }

        foreach ($citys as $city) {
            static::$city_codes_ids[ (string) $city->city_code ] = $city->id;
        }

        foreach ($alt_names as $alt_name) {
            static::$city_names_ids[ $alt_name->alt_name ] = $alt_name->city;
        }

        foreach ($citys as $city) {
            static::$citys_by_id[ $city->id ] = $city;
        }
    }

    public static function getCityId(string $name, string $city_code = null): int
    {
        if (!empty($city_code)) {
            return static::getCityIdByCityCode($city_code);
        }

        return static::getCityIdByName($name);
    }

    public static function getCityIdByName(string $name): int
    {
        if (empty(static::$city_names_ids)) {
            static::cacheInit();
        }

        return static::$city_names_ids[ $name ] ?? -1;
    }

    public static function getCityIdByCityCode(string $city_code): int
    {
        if (empty(static::$city_codes_ids)) {
            static::cacheInit();
        }

        return static::$city_codes_ids[ (string) $city_code ] ?? -1;
    }

    public static function getCityByCityCode(string $city_code): ?Citys
    {
        $city = Citys::find()
            ->where(['city_code' => $city_code])
            ->limit(1)
            ->one();

        return $city;
    }

    public static function getById(int $id): ?Citys
    {
        return static::$citys_by_id[ $id ] ?? null;
    }

    public static function existsCityName(string $name): bool
    {
        if (empty(static::$city_names_ids)) {
            static::cacheInit();
        }

        return array_key_exists($name, static::$city_names_ids);
    }

    public static function findUnknowCitys(array $citys_names): array
    {
        $unknow_citys = [];

        for ($i = 0; $i < count($citys_names); $i++) {
            if (!is_string($citys_names[$i])) {
                continue;
            }

            if (!static::existsCityName($citys_names[$i])) {
                $unknow_citys[] = $citys_names[$i];
            }
        }

        return $unknow_citys;
    }

    /**
     * @param string[] $alt_names
     * @return void
     */
    public function updateAltNames(array $alt_names)
    {
        AltCitysNames::deleteAll(['city' => $this->id]);

        $alt_names = array_unique($alt_names);

        foreach ($alt_names as $alt_name) {
            $curr_alt_name = new AltCitysNames();
            $curr_alt_name->city = $this->id;
            $curr_alt_name->alt_name = $alt_name;
            $curr_alt_name->save(false);
        }
    }
}
