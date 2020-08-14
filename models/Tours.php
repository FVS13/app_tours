<?php

namespace app\models;

use Yii;
use app\models\FlightData;
use app\models\InvalidTours;
use app\models\Trip;
use app\models\ReaderTours;
use app\exceptions\ParseTourFileException;
use app\exceptions\SaveTourException;
use app\exceptions\UnknownCityException;
use yii\base\ActionEvent;
use yii\base\Component;
use yii\db\Query;

class Tours extends Component
{
    public const EVENT_PROGRESS = 'progress';

    public function parse(string $root, int $parse_number)
    {
        $reader_tours = new ReaderTours($root, \Yii::$app->params['mark_read_json_files']);

        $is_transaction_started = false;
        $count_tours = 0;
        $parsed_tours = 0;
        $progress_event = new ActionEvent(static::EVENT_PROGRESS);

        foreach ($reader_tours->getAllToursFiles() as $file_content) {
            $path_info = ReaderTours::getInfoFromPath($reader_tours->current_filepath);

            try {
                $tours = $reader_tours->getToursFromFile($file_content);
            } catch (ParseTourFileException $th) {
                static::addTourError($parse_number, $path_info, 0, 'parse_tour_file');
                continue;
            }

            $parsed_tours += count($tours);
            $progress_event->result = ['progress' => $parsed_tours];
            $this->trigger(static::EVENT_PROGRESS, $progress_event);

            if (!$is_transaction_started) {
                $transaction = Yii::$app->db->beginTransaction();
                $is_transaction_started = true;
            }

            $max_collected_at_gmt = ReaderTours::getMaxCollectedGmt($tours);

            static::deleteTours($path_info, $max_collected_at_gmt);

            $validator = new ValidationTour($path_info['tour_type']);
            $errors = [];

            foreach ($tours as $tour) {
                if (!$validator->validate($tour)) {
                    if (!empty($validator->errors)) {
                        $errors['validation'][] = ['errors' => $validator->errors] + $tour;
                    } elseif (!empty($validator->excluding_errors)) {
                        $errors['excluding'][] = ['excluding_errors' => $validator->excluding_errors] + $tour;
                    }

                    continue;
                }

                try {
                    static::addTour($parse_number, $tour, $path_info);
                } catch (\Throwable $th) {
                    if ($th instanceof \app\exceptions\UnknownCityException) {
                        $error_text = static::getUnknowCityHandler($th);
                        $errors['unknow_city'][] = ['error' => $error_text] + $tour;
                    } elseif (
                        $th instanceof \app\exceptions\SaveTourException
                        || $th instanceof \yii\db\IntegrityException
                        || $th instanceof \yii\db\Exception
                    ) {
                        $error_text = static::getUnsavedHandler($th);
                        $errors['save'][] = ['error' => $error_text] + $tour;
                    } else {
                        $error_text  = 'Произошла неизвестная ошибка при сохранении записи о рейсе: ';
                        $error_text .= $th->getMessage();

                        Yii::error($error_text);
                        $transaction->commit();

                        throw $th;
                    }
                }
            }

            $count_tours += count($tours);

            if ($count_tours >= 300) {
                $transaction->commit();
                $is_transaction_started = false;
                $count_tours = 0;
            }

            static::errorsHandler($parse_number, $path_info, $errors);
        }

        if ($is_transaction_started) {
            $transaction->commit();
        }
    }

    private static function getUnsavedHandler(\Throwable $th): string
    {
        if ($th instanceof \app\exceptions\SaveTourException) {
            $error_text = 'При сохранении записи о рейсе произошла неизвестная ошибка';
        } else {
            $error_text  = 'Ошибка при сохранении записи о рейсе: ';
            $error_text .= $th->getMessage();
        }

        return $error_text;
    }

    /**
     * Добавляет неизвестные города в базу данных
     * Возвращает корректное сообщение об ошибке
     *
     * @param UnknownCityException $th
     * @return string
     */
    private static function getUnknowCityHandler(UnknownCityException $th): string
    {
        $new_citys = explode(', ', $th->getMessage());
        // $new_citys = array_unique($new_citys);

        foreach ($new_citys as $new_city) {
            NewCitys::addCity($new_city);
        }

        if (1 === count($new_citys)) {
            $error_text = 'Неизвестный город: ' . $new_citys[0];
        } else {
            $error_text = 'Неизвестные города: ' . implode(', ', $new_citys);
        }

        return $error_text;
    }

    /**
     * @param integer $errors ['type' => [error_tours]]
     * @return void
     */
    private static function errorsHandler(int $parse_number, array $path_info, array $errors)
    {
        foreach ($errors as $error_type => $error_tours) {
            $count = count($error_tours);

            if (0 === $count) {
                continue;
            }

            $tours_file_path = implode('/', [
                $path_info['validity'],
                $path_info['route_code'],
                $path_info['tour_type'],
                $path_info['date'] . '.json',
            ]);

            static::addTourError($parse_number, $path_info, $count, $error_type);
            static::saveInvalidTours($tours_file_path, $error_tours, $error_type);
        }
    }

    private static function addTourError(int $parse_number, array $path_info, int $errors_count, string $error_type)
    {
        InvalidTours::addTourFileError($parse_number, $path_info, $errors_count, $error_type);
    }

    private static function saveInvalidTours(string $file_name, array $tours, string $error_type)
    {
        $incorrect_tours_dir = \Yii::$app->params['incorrect_tours_dir'];
        $incorrect_tours_dir = realpath($incorrect_tours_dir);

        if (false === $incorrect_tours_dir) {
            \Yii::warning('Директория incorrect_tours/ не существует');
        }

        $path_to_invalid_tours = implode('/', [$incorrect_tours_dir, $error_type]);

        try {
            if (!file_exists($path_to_invalid_tours)) {
                mkdir($path_to_invalid_tours, 0755, true);
            }
        } catch (\Throwable $th) {
            \Yii::error("Не удалось создать директорию $path_to_invalid_tours");
            return;
        }

        $file_content = json_encode($tours, ExportTours::$jsons_format);

        $file_path = $path_to_invalid_tours . '/' . $file_name;

        if (!file_exists(dirname($file_path))) {
            mkdir(dirname($file_path), 755, true);
        }

        file_put_contents($file_path, $file_content);
    }

    private static function addTour(int $parse_number, array $tour, array $path_info)
    {
        $tour['collected_at_gmt'] = preg_replace('/Z.*$/', '', $tour['collected_at_gmt']);

        if ('BUS' === $path_info['tour_type']) {
            $trip = new Trip();
            $trip->attributes = $tour;
            $trip->validity = $path_info['validity'];
            $trip->route_code = $path_info['route_code'];
            $trip->parse_number = $parse_number;

            if (!$trip->save()) {
                throw new SaveTourException('Произошла ошибка при сохранении автобусного рейса');
            }
        } else {
            FlightData::addFlightData($parse_number, $tour, $path_info);
        }
    }

    private static function deleteTours(array $path_info, string $max_collected_at_gmt = null)
    {
        if (empty($max_collected_at_gmt)) {
            $max_collected_at_gmt = gmdate('Y-m-d H:i:s');
        }

        $max_collected_at_gmt = Harmonize::datetimeMsk($max_collected_at_gmt, '0');

        if ('BUS' === $path_info['tour_type']) {
            Trip::deleteTrips($path_info, $max_collected_at_gmt);
        } else {
            Flight::deleteFlights($path_info, $max_collected_at_gmt);
        }
    }

    /**
     * @return string[] Список типов рейсов
     */
    public static function getToursTypesList(): array
    {
        return ['C', 'Y', 'BUS'];
    }

    /**
     * @param string $name Тип рейса
     * @return string Понятное человеку название типа рейса
     */
    public static function getTourTypeLabel(string $name): string
    {
        $tours_types = [
            'C' => 'Авиа-бизнес',
            'Y' => 'Авиа-эконом',
            'BUS' => 'Автобусы',
        ];

        return $tours_types[ $name ] ?? '';
    }

    /**
     * @param string $name Тип рейса
     * @return string Краткое, понятное человеку название типа рейса
     */
    public static function getTourTypeShortLabel(string $name): string
    {
        $tours_types = [
            'C' => 'А-Б',
            'Y' => 'А-Э',
            'BUS' => 'Авто',
        ];

        return $tours_types[ $name ] ?? '';
    }


    /**
     * Получает целевые, отслеживаемые даты для выгрузки
     *
     * @return string[]
     */
    public static function getTargetDates(): array
    {
        $curr_time = time() + 60 * 60 * 3;

        $numbers_dates = array_merge(
            range(0, 94),
            [99, 109, 119, 149, 179, 209, 269, 329]
        );

        $target_dates = [];

        foreach ($numbers_dates as $number_date) {
            $target_dates[] = date('Y-m-d', $curr_time + $number_date * 60 * 60 * 24);
        }

        return $target_dates;
    }

    /**
     * Получает целевые, отслеживаемые даты для отображения статистики в админке;
     * Даты отображаются так, как будто уже наступил заврашний день,
     * Но сегодняшний день тоже учитывается
     */
    public static function getCondForTargetDates(string $date_field): array
    {
        $current_time = time();
        $today = date('Y-m-d', $current_time);
        $date96 = date('Y-m-d', $current_time + 60 * 60 * 24 * 96);
        $condition = [
            'or',
            ['between', $date_field, $today, $date96],
        ];

        $arr_for_in = [];
        $dates = [100, 110, 120, 150, 180, 210, 270, 330];

        foreach ($dates as $date_number) {
            $arr_for_in[] = date('Y-m-d', $current_time + 60 * 60 * 24 * $date_number);
        }

        $condition[] = [$date_field => $arr_for_in];

        return $condition;
    }

    /**
     * Функция для удаления устаревших записей в БД
     *
     * @param integer $limit Количество суток, начиная с текущего момента, которые не считаются устаревшими
     * @return void
     */
    public static function removeOlderTours(int $limit)
    {
        $last_tour = ToursReports::find()
            ->where([
                'and',
                ['<', 'start_time', date('Y-m-d H:i:s', time() - $limit * 24 * 60 * 60)],
            ])
            ->orderBy('parse_number DESC')
            ->limit(1)
            ->one();

        if (empty($last_tour)) {
            return true;
        }

        $last_parse_number = $last_tour->parse_number;

        static::deleteOlderTours(Flight::tableName(), 50000, $last_parse_number);
        static::deleteOlderTours(InvalidTours::tableName(), 50000, $last_parse_number);
        static::deleteOlderTours(Trip::tableName(), 50000, $last_parse_number);

        IcaoCodes::deleteAll('`date` < CURRENT_DATE');
    }

    private static function deleteOlderTours(string $table_name, int $number_tours, int $parse_number)
    {
        do {
            \Yii::$app->db
                ->createCommand(
                    "DELETE FROM $table_name
                    WHERE `parse_number` <= $parse_number
                    LIMIT $number_tours"
                )
                ->execute();

            $exists_tours = (new Query())
                ->select('id')
                ->from($table_name)
                ->where([
                    'and',
                    ['<=', 'parse_number', $parse_number],
                ])
                ->exists();
        } while ($exists_tours);
    }
}
