<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property string $collected_at_gmt Время сбора данных о рейсе парсером
 * @property string $validity Источник, из которого была получена информация об этом рейсе
 * @property integer $departure_city Id города прибытия
 * @property integer $arrival_city Id города отправления
 * @property string $departure_time Дата отправления
 * @property string $arrival_time Дата прибытия
 * @property string $departure_city_start Название места, где люди садятся в автобус
 * @property string $arrival_city_end Название места, где людей высаживают из автобуса
 * @property string $seats_count Количество свободных мест
 * @property int $price Цена проезда
 * @property string $marketing_carrier Название перевозчика
 */
class Trip extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%trips}}';
    }

    public function scenarios()
    {
        return [
            'default' => [
                'validity', 'collected_at_gmt', 'route_code',
                'seats_count', 'price',
                'bus_num', 'marketing_carrier',
                'departure_city_start', 'arrival_city_end',
                'departure_time', 'arrival_time',
            ],
        ];
    }

    public function getDepartureCity()
    {
        return Citys::getById($this->departure_city);
        // return $this->hasOne(Citys::class, ['id' => 'departure_city']);
    }

    public function getArrivalCity()
    {
        return Citys::getById($this->arrival_city);
        // return $this->hasOne(Citys::class, ['id' => 'arrival_city']);
    }

    /**
     * Гармонизация данных поездки
     *
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->collected_at_gmt = Harmonize::datetimeMsk($this->collected_at_gmt, '0');

            list($route_from_code, $route_to_code) = Routes::getCitysCodes($this->route_code);
            $current_route = Routes::getRouteByCitysCodes($route_from_code, $route_to_code);

            $this->harmonizeArrivalTime($current_route->distance_by_roads);
            $this->harmonizeCity('departure', 'start', $route_from_code);
            $this->harmonizeCity('arrival', 'end', $route_to_code);

            if (!empty($this->marketing_carrier['name'])) {
                $this->marketing_carrier = $this->marketing_carrier['name'];
                $this->marketing_carrier = Harmonize::quotes($this->marketing_carrier);
            } else {
                $this->marketing_carrier = '';
            }

            $this->seats_count = Harmonize::seatsCount($this->seats_count ?? '', '10+');
            return true;
        }

        return false;
    }

    private function harmonizeArrivalTime(int $distance_by_roads)
    {
        $speed = 50;

        if (empty($this->arrival_time)) {
            $duration_in_hours = round($distance_by_roads / $speed, 0, PHP_ROUND_HALF_UP) + 1;

            $this->arrival_time = date('Y-m-d H:i:s', strtotime($this->departure_time) + $duration_in_hours * 60 * 60);
        }
    }

    private function harmonizeCity(string $field_name, string $suffix, string $route_code)
    {
            $field_name_city = $field_name . '_city';
            $field_name_city_suffix = $field_name_city . '_' . $suffix;

            $city = Citys::getCityByCityCode($route_code);
            $city_gmt = $city->time_zone_gmt;

            $this->{$field_name_city} = $city->id;
            $this->{$field_name_city_suffix} = $this->{$field_name_city_suffix}['name'] ?? $city->name;
            $this->{$field_name_city_suffix} = Harmonize::quotes($this->{$field_name_city_suffix});
            $this->{$field_name . '_time_msk'} = Harmonize::datetimeMsk($this->{$field_name . '_time'}, $city_gmt);
    }

    public static function deleteTrips(array $path_info, string $max_collected_at_gmt)
    {
        $cond = [
            'and',
            ['route_code' => $path_info['route_code']],
            ['validity' => $path_info['validity']],
            new Expression('DATE(departure_time) = :date', [':date' => $path_info['date']]),
            ['<=', 'collected_at_gmt', $max_collected_at_gmt],
        ];

        Trip::updateAll(['is_deleted' => true], $cond);
    }



    public function getAsArray(): array
    {
        $result_array = [];

        $result_array['collected_at_gmt'] = $this->collected_at_gmt;
        $result_array['validity'] = $this->validity;

        $result_array['departure_city_start'] = $this->departure_city_start;
        $result_array['arrival_city_end'] = $this->arrival_city_end;

        $result_array['departure_city'] = $this->departureCity->name;
        $result_array['arrival_city'] = $this->arrivalCity->name;

        $result_array['marketing_carrier'] = $this->marketing_carrier;

        $result_array['bus_num'] = $this->bus_num;

        $result_array['departure_time'] = Harmonize::datetimeMsk($this->departure_time, '+3');
        $result_array['departure_time_msk'] = Harmonize::datetimeMsk($this->departure_time_msk, '+3');
        $result_array['arrival_time'] = Harmonize::datetimeMsk($this->arrival_time, '+3');
        $result_array['arrival_time_msk'] = Harmonize::datetimeMsk($this->arrival_time_msk, '+3');

        $result_array['seats_count'] = $this->seats_count;
        $result_array['price'] = (string) $this->price;

        return $result_array;
    }
}
