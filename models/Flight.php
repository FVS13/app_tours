<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii\db\IntegrityException;

/**
 * @property string $collected_at_gmt Время сбора данных о рейсе парсером
 * @property string $service_class Класс обслуживания
 * @property string $validity Источник, из которого была получена информация об этом рейсе
 * @property FlightSegment[] $segments
 * @property FlightTariffsInfo[] $fares
 */
class Flight extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%flights}}';
    }

    public function scenarios()
    {
        return [
            'default' => ['validity', 'route_code', 'service_class', 'date'],
        ];
    }

    public function getSegments()
    {
        return $this->hasMany(FlightSegment::class, ['flight_ref' => 'id']);
    }

    public function getFares()
    {
        return $this->hasMany(FlightTariffsInfo::class, ['flight_ref' => 'id']);
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->collected_at_gmt = Harmonize::datetimeMsk($this->collected_at_gmt, '0');

            return true;
        }

        return false;
    }

    public static function deleteFlights(array $path_info, string $max_collected_at_gmt)
    {
        $cond = [
            'and',
            ['route_code' => $path_info['route_code']],
            ['service_class' => $path_info['tour_type']],
            ['validity' => $path_info['validity']],
            ['date' => $path_info['date']],
            ['<=', 'collected_at_gmt', $max_collected_at_gmt],
        ];

        Flight::updateAll(['is_deleted' => true], $cond);
    }

    /**
     * Получает из текущего объекта массив свойств, отформатированный для выгрузки
     *
     * @throws IntegrityException Выбрасывается при отсутствии обязательных связанных данных
     * @return array
     */
    public function getAsArray(): array
    {
        if (empty($this->fares)) {
            throw new IntegrityException("getAsArray() авиа рейс (id: $this->primaryKey) без тарифов");
        }

        if (empty($this->segments)) {
            throw new IntegrityException("getAsArray() авиа рейс (id: $this->primaryKey) без сегментов");
        }

        $result_array = [];

        $result_array['collected_at_gmt'] = $this->collected_at_gmt;
        $result_array['validity'] = $this->validity;

        foreach ($this->fares as $tariff) {
            $curr_tariff = $tariff->getAsArray();
            $curr_tariff['service_class'] = $this->service_class;

            $result_array['fares'][] = $curr_tariff;
        }

        foreach ($this->segments as $segment) {
            $result_array['segment_' . $segment->segment_number] = $segment->getAsArray();
        }

        if (1 == count($this->segments) && 2 == $segment->segment_number) {
            throw new IntegrityException("getAsArray() Нет первого сегмента авиа рейса (id: $this->primaryKey)");
        }

        return $result_array;
    }
}
