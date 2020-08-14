<?php

namespace app\models;

use app\exceptions\SaveTourException;
use app\exceptions\UnknownCityException;

class FlightData
{
    /**
     * Сохраняет все данные авиарейса в базу данных
     *
     * @param integer $parse_number
     * @param array $tour
     * @param array $path_info
     * @return void
     */
    public static function addFlightData(int $parse_number, array $tour, array $path_info)
    {
        $flight_id = static::addFlight($parse_number, $tour, $path_info);

        $segment_attributes = (new FlightSegment())->attributes();
        $fares_attributes = (new FlightTariffsInfo())->attributes();

        unset($segment_attributes['id'], $fares_attributes['id']);


        \Yii::$app->db->createCommand()
            ->batchInsert(
                FlightSegment::tableName(),
                $segment_attributes,
                static::getSegments($flight_id, $path_info['route_code'], $tour)
            )
            ->execute();

        \Yii::$app->db->createCommand()
            ->batchInsert(
                FlightTariffsInfo::tableName(),
                $fares_attributes,
                FlightTariffsInfo::getTariffInfo($flight_id, $tour['fares'])
            )
            ->execute();


        static::addIcaoCodes($tour);
    }

    /**
     * Сохраняет основные данные авиарейса, без сегментов и тарифов
     *
     * @param integer $parse_number
     * @param array $tour
     * @param array $path_info
     * @return void
     */
    private static function addFlight(int $parse_number, array $tour, array $path_info): int
    {
        $flight = new Flight();
        $flight->attributes = $path_info;
        $flight->parse_number = $parse_number;
        $flight->collected_at_gmt = $tour['collected_at_gmt'];
        $flight->service_class = $path_info['tour_type'];
        $flight->validity = $path_info['validity'];

        if (!$flight->save()) {
            throw new SaveTourException('Ошибка сохранения авиаперелёта');
        }

        return $flight->id;
    }

    private static function getSegments(int $flight_id, string $route_code, array $tour)
    {
        list($route_from_code, $route_to_code) = Routes::getCitysCodes($route_code);

        $segments_data = [];

        if (empty($tour['segment_2'])) {
            $segments_data[] = FlightSegment::getSegmentData(
                $flight_id,
                1,
                $tour['segment_1'],
                $route_from_code,
                $route_to_code
            );

            return $segments_data;
        }

        if (empty($tour['segment_1']['arrival_city']['name'])) {
            $tour['segment_1']['arrival_city']['name'] = $tour['segment_2']['departure_city']['name'];
            $tour['segment_1']['arrival_city']['code'] = $tour['segment_2']['departure_city']['code'];
        }

        if (empty($tour['segment_2']['departure_city']['name'])) {
            $tour['segment_2']['departure_city']['name'] = $tour['segment_1']['arrival_city']['name'];
            $tour['segment_2']['departure_city']['code'] = $tour['segment_1']['arrival_city']['code'];
        }

        if (!empty($tour['segment_2'])) {
            $unknown_citys = Citys::findUnknowCitys([
                $tour['segment_1']['arrival_city']['name'],
                $tour['segment_2']['departure_city']['name'],
            ]);

            if (!empty($unknown_citys)) {
                throw new UnknownCityException(implode(', ', $unknown_citys));
            }
        }

        $segments_data[] = FlightSegment::getSegmentData($flight_id, 1, $tour['segment_1'], $route_from_code);
        $segments_data[] = FlightSegment::getSegmentData($flight_id, 2, $tour['segment_2'], null, $route_to_code);

        return $segments_data;
    }

    public static function addIcaoCodes(array $tour)
    {
        try {
            for ($i = 1; $i <= 2; $i++) {
                $segment = $tour['segment_' . $i] ?? null;

                if (empty($segment)) {
                    continue;
                }

                $airline_code = Airlines::getMainCode($segment['marketing_carrier']['code']);

                IcaoCodes::addNewCode(
                    $airline_code,
                    $segment['flight_num'],
                    date('Y-m-d H:i:s', strtotime($segment['departure_time'])),
                    $segment['airplane'] ?? null
                );
            }
        } catch (\Throwable $th) {
            \Yii::error('FlightData::addIcaoCodes():');
            \Yii::error($th);
        }
    }
}
