<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property integer $id
 * @property integer $location Id города, в котором находится аэропорт
 * @property string $airport_code
 * @property Citys $locationCity Город, в котором находится аэропорт
 * @property Citys $locationCityFromCache
 */
class Airports extends ActiveRecord
{
    use \app\traits\CachingActiveRecords;

    public function getLocationCity()
    {
        return $this->hasOne(Citys::class, ['id' => 'location']);
    }

    public function getLocationCityFromCache(): Citys
    {
        return Citys::getById($this->location);
    }

    public function scenarios()
    {
        return [
            'default' => ['id', 'location', 'airport_code'],
        ];
    }

    public function rules()
    {
        return [
            ['id', 'integer', 'min' => 1, 'message' => 'Некорректный id аэропорта'],
            ['location', 'required', 'message' => 'Не указан город нахождения'],
            ['location', 'isUniqueLocation'],
            ['location', 'existsLocation'],
            ['airport_code', 'trim'],
            ['airport_code', 'required', 'message' => 'Не указан код аэропорта'],
            ['airport_code', 'string', 'max' => 4, 'tooLong' => 'Код аэропорта должен быть не больше 4 символов'],
            ['airport_code', 'isUniqueAirportCode'],
        ];
    }

    public function isUniqueAirportCode(string $attribute)
    {
        $another_airport = static::findOne(['airport_code' => $this->$attribute]);

        if (!empty($another_airport) && $another_airport->id !== (int) $this->id) {
            $this->addError($attribute, 'Аэропорт с таким кодом уже существует');
        }
    }

    public function isUniqueLocation(string $attribute)
    {
        $another_airport = static::findOne(['location' => $this->$attribute]);

        if (!empty($another_airport) && $another_airport->id !== (int) $this->id) {
            $this->addError($attribute, 'Аэропорт в этом городе уже есть');
        }
    }

    public function existsLocation(string $attribute)
    {
        $location = Citys::findOne($this->$attribute);

        if (empty($location)) {
            $this->addError($attribute, "Невозможно добавить аэропорт. Нет города  нахождния (id: $this->$attribute)");
        }
    }
}
