<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property Airlines $carrier Авиакомпания
 * @property Citys $departureCity Город отправления
 * @property Citys $arrivalCity Город прибытия
 * @property string $flight_num Номер рейса
 * @property string $departure_time Дата отправления
 * @property string $arrival_time Дата прибытия
 * @property integer $departure_city Id города прибытия
 * @property integer $arrival_city Id города отправления
 * @property integer $segment_number Номер сегмента
 */
class FlightSegment extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%flights_segments}}';
    }

    public function getCarrier()
    {
        return Airlines::getById($this->marketing_carrier);
        // return $this->hasOne(Airlines::class, ['id' => 'marketing_carrier']);
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

    public function scenarios()
    {
        return [
            'default' => ['flight_num', 'departure_time', 'arrival_time'],
        ];
    }

    /**
     * Гармонизация данных одного сегмента перелёта
     *
     */
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
        $departure_city = Citys::findOne($this->departure_city);
        $arrival_city = Citys::findOne($this->arrival_city);

        $departure_city_gmt = $departure_city->time_zone_gmt;
        $arrival_city_gmt   = $arrival_city->time_zone_gmt;

        $this->departure_time_msk = Harmonize::datetimeMsk($this->departure_time, $departure_city_gmt);
        $this->arrival_time_msk   = Harmonize::datetimeMsk($this->arrival_time, $arrival_city_gmt);
    }

    public static function getSegmentData(
        int $flight_ref,
        int $segment_number,
        array $segment_arr,
        string $route_from_code = null,
        string $route_to_code = null
    ): array {
        $segment = new static();
        $segment->attributes = $segment_arr;
        $segment->segment_number = $segment_number;
        $segment->flight_ref = $flight_ref;

        $segment->marketing_carrier = Airlines::findOrCreateId($segment_arr['marketing_carrier']);
        $segment->departure_city = Citys::getCityId($segment_arr['departure_city']['name'], $route_from_code);
        $segment->arrival_city = Citys::getCityId($segment_arr['arrival_city']['name'], $route_to_code);

        $segment->harmonize();

        return array_values($segment->attributes);
    }

    /**
     * Получает название модели и вместимость самолёта
     *
     * @return array
     */
    public function getAirplaneData(): array
    {
        $default_aircraft_code = 'S'; # Код стандартной авиакомпании
        $defaul_empty_value = [
            'airplane_model' => 'None',
            'passenger_capacity' => 'None',
        ];

        $airline_code = $this->carrier->code ?? $default_aircraft_code;

        $icao_code = IcaoCodes::getIcao($airline_code, $this->flight_num, $this->departure_time);

        if (empty($icao_code)) {
            return $defaul_empty_value;
        }

        $airplane_data = AircraftReference::getByAirlineAndIcao($airline_code, $icao_code)
            ?? AircraftReference::getByAirlineAndIcao($default_aircraft_code, $icao_code);

        return [
            'airplane_model' => $airplane_data->airplane_model ?? $defaul_empty_value['airplane_model'],
            'passenger_capacity' => $airplane_data->passenger_capacity ?? $defaul_empty_value['passenger_capacity'],
        ];
    }

    /**
     * Получает из текущего объекта массив свойств, отформатированный для выгрузки
     *
     * @return array
     */
    public function getAsArray(): array
    {
        $result_array = [];

        $result_array['flight_num'] = $this->flight_num;

        $airplane_data = $this->getAirplaneData();
        $result_array['airplane'] = $airplane_data['airplane_model'];
        $result_array['passenger_capacity'] = (string) $airplane_data['passenger_capacity'];

        $result_array['departure_time'] = Harmonize::datetimeMsk($this->departure_time, '+3');
        $result_array['departure_time_msk'] = Harmonize::datetimeMsk($this->departure_time_msk, '+3');
        $result_array['arrival_time'] = Harmonize::datetimeMsk($this->arrival_time, '+3');
        $result_array['arrival_time_msk'] = Harmonize::datetimeMsk($this->arrival_time_msk, '+3');

        $result_array['marketing_carrier']['name'] = $this->carrier->name ?? '';
        $result_array['marketing_carrier']['code'] = $this->carrier->code;

        $departure_city = $this->departureCity->bindedAirportLocationFromCache ?? $this->departureCity;
        $arrival_city = $this->arrivalCity->bindedAirportLocationFromCache ?? $this->arrivalCity;

        $result_array['departure_city']['name'] = $departure_city->name;
        $result_array['arrival_city']['name'] = $arrival_city->name;

        $result_array['departure_city']['code'] = $departure_city->airport->airport_code;
        $result_array['arrival_city']['code'] = $arrival_city->airport->airport_code;

        return $result_array;
    }
}
