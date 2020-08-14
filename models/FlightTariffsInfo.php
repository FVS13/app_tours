<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property string $seats_count Количество свободных мест
 * @property int $price Цена перелёта
 * @property string $currency_code Валюта
 * @property string $tariffName Название тарифа, из кеша
 * @property int $fare_route Id названия тарифа в таблице `tariffs_names`
 */
class FlightTariffsInfo extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%flights_tariffs_info}}';
    }

    public function scenarios()
    {
        return [
            'default' => ['seats_count', 'price', 'currency_code'],
        ];
    }

    /**
     * Метод для получения строки с названием тарифа
     *
     * @return string Название тарифа
     */
    public function getTariffName(): string
    {
        return TariffsNames::getById($this->fare_route)->name;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->harmonize();
            return true;
        }

        return false;
    }

    public function harmonize()
    {
        $this->seats_count = Harmonize::seatsCount($this->seats_count ?? '', '5+');
    }

    public static function getTariffInfo(int $flight_ref, array $fares): array
    {
        $fares_data = [];

        foreach ($fares as $fares_item) {
            $tariff_info = new FlightTariffsInfo();
            $tariff_info->attributes = $fares_item;
            $tariff_info->flight_ref = $flight_ref;
            $tariff_info->fare_route = TariffsNames::findOrCreateId($fares_item['fare_route']);

            $tariff_info->harmonize();

            $fares_data[] = $tariff_info->attributes;
        }

        return $fares_data;
    }

    public function getAsArray(): array
    {
        $result_array = [];

        $result_array['currency_code'] = $this->currency_code;
        $result_array['seats_count'] = $this->seats_count;
        $result_array['price'] = (string) $this->price;
        $result_array['fare_route'] = $this->tariffName;

        return $result_array;
    }
}
