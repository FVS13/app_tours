<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii\db\Expression;

class IcaoCodes extends ActiveRecord
{
    private static $codes = null;
    public const SEATGURU_AIRLINES = [
        'SU', 'S7', 'J2', 'KC', 'AZ', 'TK', 'LH', 'UT', 'FZ',
        'HY', 'EK', 'CZ', 'GF', 'BT', 'N4', 'KK', 'OS', 'LO',
        'IG', '5F', 'HU', 'QR', 'KE', 'MS', 'CA', 'EY', 'MU',
        'ET', 'OZ', 'TG', 'AY', 'AF', 'JL', 'VN', 'LX', 'KL',
        'BA', 'OK', 'SQ', 'CX', 'VS', 'SN', 'KA', 'FM', 'LY',
        'FV', 'JU', 'PC', 'PS', 'A3', 'TP', '4U', 'KM', 'IB',
        'VY', 'EI', 'WK', 'EN', 'AT', 'MF', 'PK', 'UX', 'RJ',
        'ME', 'HX', 'EW', 'W6', 'SK', 'KQ'
    ];

    public static function tableName()
    {
        return '{{%icao_codes}}';
    }

    public static function addNewCode(
        string $airline_code,
        string $flight_num,
        string $date,
        string $icao = null
    ): bool {
        $exists_code = static::existsCode($airline_code, $flight_num, $date);
        $key = static::generateHash($airline_code, $flight_num, $date);

        if (empty($icao) && $exists_code) {
            return false;
        }

        if ($exists_code) {
            if (empty($icao) || $icao === static::$codes[ $key ]) {
                return false;
            }

            $code = static::findOne([
                'airline_code' => $airline_code,
                'flight_num' => $flight_num,
                'date' => $date,
            ]);
        } else {
            $code = new static();
            $code->airline_code = $airline_code;
            $code->flight_num = $flight_num;
            $code->date = $date;
        }

        static::$codes[ $key ] = (string) $icao;

        $code->icao = $icao;
        return $code->save();
    }

    public static function existsCode(string $airline_code, string $flight_num, string $date): bool
    {
        if (empty(static::$codes)) {
            static::cacheInit();
        }

        $key = static::generateHash($airline_code, $flight_num, $date);

        return array_key_exists($key, static::$codes ?? []);
    }

    private static function generateHash(string $airline_code, string $flight_num, string $date)
    {
        $date = preg_replace('/ /', 'T', $date);
        $code = implode('::', [(string) $airline_code, (string) $flight_num, (string) $date]);
        return $code;
    }

    public function getHash()
    {
        return static::generateHash($this->airline_code, $this->flight_num, $this->date);
    }

    public static function cacheInit()
    {
        foreach (static::find()->each() as $code) {
            static::$codes[ $code->hash ] = $code->icao;
        }
    }

    public static function getIcao(string $airline_code, string $flight_num, string $date): ?string
    {
        $key = static::generateHash($airline_code, $flight_num, $date);

        return static::$codes[ $key ] ?? null;
    }

    public static function complete()
    {
        foreach (MicroAirlines::find()->each() as $micro_airline) {
            static::updateAll(
                ['icao' => $micro_airline->airplane_icao],
                ['airline_code' => $micro_airline->airline_code]
            );
        }
    }

    public static function getCondForNewIcaos(): array
    {
        return [
            'or',
            ['updated_at' => null],
            ['<=', 'updated_at', date('Y-m-d H:i:s', time() - 1 * 24 * 60 * 60)],
        ];
    }

    private static function getIcaos(string $format_name = 'ozon', bool $all_nulls_icaos = false): \Iterator
    {
        $cond = [
            'and',
            ['icao' => null],
            new Expression('DATE(`date`) >= CURRENT_DATE'),
        ];

        if (!$all_nulls_icaos) {
            $cond[] = static::getCondForNewIcaos();
        }

        if ('seatguru' === $format_name) {
            $cond[] = ['airline_code' => static::SEATGURU_AIRLINES];
        }

        $icao_codes = IcaoCodes::find()
            ->where($cond)
            // ->limit(3)
            ->each(50000);

        return $icao_codes;
    }

    private static function formatIcaoCodes(\Iterator $icao_codes, string $format_name)
    {
        $flights = [];

        foreach ($icao_codes as $icao_code) {
            switch ($format_name) {
                case 'ozon':
                    $current_flight = FlightSegment::findOne([
                        'marketing_carrier' => Airlines::findOrCreateId(['code' => $icao_code->airline_code]),
                        'flight_num' => $icao_code->flight_num,
                        'departure_time' => $icao_code->date,
                    ]);

                    if (empty($current_flight)) {
                        continue 2;
                    }

                    $route = $current_flight->departureCity->name . '_' . $current_flight->arrivalCity->name;
                    $curr_date = date('Y-m-d', strtotime($icao_code->date));

                    $flights[ $route ]['route'] = $route;
                    $flights[ $route ]['flight_dates'][ $curr_date ][] = [
                        'flight_num' => $icao_code->flight_num,
                        'flight_code' => $icao_code->airline_code,
                        'date' => Harmonize::datetimeMsk($icao_code->date, '+3'),
                        'ICAO' => null,
                    ];

                    break;
                case 'seatguru':
                    $flights[] = [
                        "num" => (string) $icao_code->flight_num,
                        "code" => (string) $icao_code->airline_code,
                        "date" => date('m/d/Y', strtotime($icao_code->date)),
                    ];

                    break;
            }
        }

        if ('seatguru' === $format_name) {
            asort($flights);
        }

        return array_values($flights);
    }

    public static function exportIcaos(
        string $file_path,
        string $format_name,
        bool $all_nulls_icaos = false
    ) {
        $icao_codes = static::getIcaos($format_name, $all_nulls_icaos);
        $flights = static::formatIcaoCodes($icao_codes, $format_name);

        $flights_json = json_encode($flights, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        FileSystemAdapter::safeWriteFile($file_path, $flights_json);
    }
}
