<?php

namespace app\models;

use yii\db\Expression;
use yii\db\Query;

class Statistics
{
    public static function getLastCollectedGmtFlight(
        string $validity = null,
        string $route_code = null,
        string $service_class = null
    ): ?string {
        $cond = [
            'validity' => $validity,
            'route_code' => $route_code,
            'service_class' => $service_class,
        ];

        $cond = array_filter($cond);

        $last_flight = Flight::find()
            ->where($cond)
            ->orderBy('collected_at_gmt DESC')
            ->limit(1)
            ->one();

        return $last_flight->collected_at_gmt ?? null;
    }

    public static function getLastCollectedGmtTrip(
        string $validity = null,
        string $route_code = null
    ): ?string {
        $cond = [
            'validity' => $validity,
            'route_code' => $route_code,
        ];

        $cond = array_filter($cond);

        $last_trip = Trip::find()
            ->where($cond)
            ->orderBy('collected_at_gmt DESC')
            ->limit(1)
            ->one();

        return $last_trip->collected_at_gmt ?? null;
    }

    /**
     * Получает список все источников, содержащихся в БД
     *
     * @return string[]
     */
    public static function getAllValidities()
    {
        $validities_from_trips = (new Query())
            ->select(['validity'])
            ->from(Trip::tableName())
            ->distinct();

        $validities_from_flights = (new Query())
            ->select(['validity'])
            ->from(Flight::tableName())
            ->distinct();

        $validities = $validities_from_trips
            ->union($validities_from_flights)
            ->all();

        $validities = array_column($validities, 'validity');

        return $validities;
    }

    /**
     * Возвращает количество дат, для которых есть хотя бы один рейс
     *
     * @param string $route_code
     * @param boolean $only_target_dates Для учёта только отслеживаемых дат
     * @return integer
     */
    public static function countDatesTrip(
        string $route_code,
        bool $only_target_dates = false
    ): int {
        $cond = [
            'and',
            ['route_code' => $route_code],
            new Expression('DATE(`departure_time`) >= CURRENT_DATE'),
            ['is_deleted' => false],
        ];

        if ($only_target_dates) {
            $target_dates_cond = Tours::getCondForTargetDates('DATE([[departure_time]])');
            $cond[] = $target_dates_cond;
        }

        $count_dates = (new Query())
            ->select('DATE(`departure_time`)')
            ->from(Trip::tableName())
            ->distinct()
            ->where($cond)
            ->count();

        return $count_dates ?? 0;
    }

    /**
     * Возвращает количество дат, для которых есть хотя бы один рейс
     *
     * @param string $route_code
     * @param boolean $only_target_dates Для учёта только отслеживаемых дат
     * @return integer
     */
    public static function countDatesFlight(
        string $route_code,
        string $service_class,
        bool $only_target_dates = false
    ): int {
        $cond = [
            'and',
            ['route_code' => $route_code],
            ['service_class' => $service_class],
            ['>=', 'date', date('Y-m-d')],
            ['is_deleted' => false],
        ];

        if ($only_target_dates) {
            $target_dates_cond = Tours::getCondForTargetDates('date');
            $cond[] = $target_dates_cond;
        }

        $count_dates = (new Query())
            ->select(['date'])
            ->from(Flight::tableName())
            ->distinct()
            ->where($cond)
            ->count();

        return $count_dates ?? 0;
    }

    /**
     * Получает список источников, содержащихся во всех рейсах
     *
     * @param string $tour_type
     * @param string $route_code
     * @return string[]
     */
    public static function getListValidities(string $tour_type, string $route_code = null): array
    {
        $table = ('BUS' === $tour_type) ? Trip::tableName() : Flight::tableName();

        $cond = [
            'route_code' => $route_code,
        ];

        if ('BUS' !== $tour_type) {
            $cond['service_class'] = $tour_type;
        }

        $validities = (new Query())
            ->select(['validity'])
            ->from($table)
            ->distinct()
            ->where($cond)
            ->all();

        $validities = array_column($validities, 'validity');

        return $validities;
    }

    /**
     * Возвращает все пары "код направления" => "источник", встречающиеся в рейсах
     *
     * @return array<string,string[]>
     */
    public static function getAllRoutesValidities()
    {
        $flight_validities = (new Query())
            // service_class нужен для использования индексов БД
            ->select(['route_code', 'service_class', 'validity'])
            ->from(Flight::tableName())
            ->distinct()
            ->all();

        $trip_validities = (new Query())
            ->select(['route_code', 'validity'])
            ->from(Trip::tableName())
            ->distinct()
            ->all();

        $all_validities = [];

        foreach ($flight_validities as $route_validity) {
            $key = $route_validity['route_code'];
            $all_validities[ $key ][] = $route_validity['validity'];
        }

        foreach ($trip_validities as $route_validity) {
            $key = $route_validity['route_code'];
            $all_validities[ $key ][] = $route_validity['validity'];
        }

        return $all_validities;
    }

    /* * * * * * * * * * * * *
     *
     *    Обобщающие методы
     *
     * * * * * * * * * * * * */


    public static function countDatesTours(
        string $tour_type,
        string $route_code,
        bool $only_target_dates = false
    ): int {
        if ('BUS' === $tour_type) {
            return static::countDatesTrip($route_code, $only_target_dates);
        } else {
            return static::countDatesFlight($route_code, $tour_type, $only_target_dates);
        }
    }

    /**
     * Возвращает массив количеств рейсов по каждому источнику
     *
     * @param boolean $today При установке считаются только сегодняшние рейсы
     * @return array
     */
    public static function getCountToursByValiditys(bool $today): array
    {
        $count_column_name = $today ? 'today' : 'all_days';
        $cond = [
            'AND',
            ['is_deleted' => false]
        ];

        if ($today) {
            $cond[] = new Expression('DATE(`collected_at_gmt`) = CURRENT_DATE');
        }

        $flights = (new Query())
            ->select([
                'validity',
                '`service_class` `tour_type`',
                'COUNT(DISTINCT `route_code`) `count_routes`',
                "COUNT(*) `$count_column_name`",
                'MAX(`collected_at_gmt`) `collected-gmt`',
            ])
            ->from(Flight::tableName())
            ->where($cond)
            ->groupBy(['validity', 'tour_type']);

        $trips = (new Query())
            ->select([
                'validity',
                "'BUS' as `tour_type`",
                'COUNT(DISTINCT `route_code`) `count_routes`',
                "COUNT(*) `$count_column_name`",
                'MAX(`collected_at_gmt`) `collected-gmt`',
            ])
            ->from(Trip::tableName())
            ->where($cond)
            ->groupBy(['validity', 'tour_type']);

        $tours = $flights
            ->union($trips, true)
            ->all();

        return $tours;
    }

    /**
     * @return array<string,array<string,string>>
     */
    public static function getCountErrorsByValiditys(): array
    {
        $last_create_task = ToursReports::getLastTask(['create'], 'start_time');

        if (
            empty($last_create_task)
            || date('Y-m-d') > date('Y-m-d', strtotime($last_create_task->start_time))
            || empty($last_create_task->parse_number)
        ) {
            return [];
        }

        return (new Query())
            ->select(['validity', 'tour_type', 'SUM(`errors_count`) `errors_count`'])
            ->from(InvalidTours::tablename())
            ->where([
                '>=', 'parse_number', $last_create_task->parse_number,
            ])
            ->groupBy(['validity', 'tour_type'])
            ->all();
    }


    public static function getViewDataStatistics(): array
    {
        $count_tours_today = static::getCountToursByValiditys(true);
        $count_tours_all_days = static::getCountToursByValiditys(false);
        $count_errors_today = static::getCountErrorsByValiditys();

        $statistics = [];

        foreach ($count_tours_today as $count_tours) {
            $validity = $count_tours['validity'];
            $tour_type = $count_tours['tour_type'];

            $statistics[$validity][$tour_type]['today'] = $count_tours['today'];
        }

        foreach ($count_tours_all_days as $count_tours) {
            $validity = $count_tours['validity'];
            $tour_type = $count_tours['tour_type'];

            $statistics[$validity][$tour_type]['all_days'] = $count_tours['all_days'];
            $statistics[$validity][$tour_type]['count_routes'] = $count_tours['count_routes'];
            $statistics[$validity][$tour_type]['collected-gmt'] = $count_tours['collected-gmt'];
        }

        foreach ($count_errors_today as $count_tours) {
            $validity = $count_tours['validity'];
            $tour_type = $count_tours['tour_type'];

            $statistics[$validity][$tour_type]['errors_count'] = $count_tours['errors_count'];
        }

        $flat_statistics = [];

        foreach ($statistics as $validity => $byTourType) {
            foreach ($byTourType as $tour_type => $byCountType) {
                $summary = [
                    'validity' => $validity,
                    'tour_type' => $tour_type,
                ];

                foreach ($byCountType as $column_name => $amount) {
                    $summary[$column_name] = $amount;
                }

                $flat_statistics[] = $summary;
            }
        }

        return $flat_statistics;
    }
}
