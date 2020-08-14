<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Если во входящих файлах встречается неизвестный город,
 * то он заносится в список новых городов;
 * Для каждого города нужно расчитывать московское время,
 * а это невозможно без знания его часового пояса
 *
 * @property string $name Название города
 */
class NewCitys extends ActiveRecord
{
    public static function tablename()
    {
        return '{{%new_citys}}';
    }

    public function rules()
    {
        return [
            ['name', 'notExistsCityName'],
        ];
    }

    public function notExistsCityName(string $attribute)
    {
        $exists_name = static::find()
            ->where(['name' => $this->$attribute])
            ->exists();

        $exists_name |=  Citys::existsCityName($this->$attribute);

        if ($exists_name) {
            $this->addError($attribute, 'Имя "' . $this->$attribute . '" уже существует');
        }
    }

    public static function addCity(string $name)
    {
        $city = new static();
        $city->name = $name;

        try {
            $city->save();
        } catch (\Throwable $th) {
            Yii::error('Ошибка при добавлении нового имени "' . $name . '"');
        }
    }
}
