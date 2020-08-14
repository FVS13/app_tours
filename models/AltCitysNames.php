<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Дополнительные названия для пересадочных аэропортов
 * Раздел "Города", вкладка "города"
 *
 * @property string $alt_name
 */
class AltCitysNames extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%alt_names}}';
    }

    public function rules()
    {
        return [
            ['alt_name', 'required', 'message' => 'Альт. название не указано'],
            [
                'alt_name',
                'string',
                'max' => 30,
                'tooLong' => 'Альт. название должно быть не больше 30 символов с пробелами'
            ],
            ['alt_name', 'trim'],
            ['alt_name', function (string $attribute) {
                if (Citys::existsCityName($this->$attribute)) {
                    $this->addError($attribute, 'Название "' . $this->$attribute . '" уже есть');
                }
            }],
            ['city', function (string $attribute) {
                $city_id = $this->$attribute;

                if (empty(Citys::findOne($city_id))) {
                    $this->addError($attribute, "Город с id: ${city_id} не существует");
                }
            }],
        ];
    }

    /**
     * Возвращает пустой массив, если ошибок нет,
     * или массив с ошибками валидации первого альт. имени,
     * не прошедшего проверку
     *
     * @param array $alt_names
     * @return array Массив ошибок валидации, пустой массив, если ошибок нет
     */
    public static function batchValidate(array $alt_names, Citys $city = null): array
    {
        if (!empty($city)) {
            $alt_names = array_diff($alt_names, $city->altNamesAsArray);
        }

        foreach ($alt_names as $alt_name) {
            if (empty($alt_name)) {
                continue;
            }

            $alt_name_model = new static();
            $alt_name_model->alt_name = $alt_name;

            if (!$alt_name_model->validate()) {
                return $alt_name_model->errors;
            }
        }

        return [];
    }
}
