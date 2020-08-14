<?php

namespace app\models;

use yii\base\Model;

class TripFile extends Model
{
    public $collected_at_gmt;
    public $validity;
    public $departure_time;
    public $arrival_time;
    public $price;

    public function scenarios()
    {
        return [
            'default' => ['collected_at_gmt', 'validity', 'departure_time', 'arrival_time', 'price'],
        ];
    }

    public function rules()
    {
        return [
            ['collected_at_gmt', 'required', 'message' => 'Не указано время сбора'],
            ['validity', 'required', 'message' => 'Не указан источник'],
            ['departure_time', 'required', 'message' => 'Не указано время отправления'],
            ['price', 'required', 'message' => 'Не указана цена'],

            ['departure_time', function (string $attribute) {
                if (!ValidationTour::checkdate($this->$attribute)) {
                    $this->addError($attribute, 'Указанной даты отправления не существует');
                }
            }],
            ['arrival_time', function (string $attribute) {
                if (!ValidationTour::checkdate($this->$attribute)) {
                    $this->addError($attribute, 'Указанной даты прибытия не существует');
                }
            }],
            ['price', function (string $attribute) {
                if (!ValidationTour::isValidPrice($this->$attribute)) {
                    $this->addError($attribute, 'Неверный формат цены');
                }
            }],
        ];
    }
}
