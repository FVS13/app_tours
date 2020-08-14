<?php

namespace app\models;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style;
use PhpOffice\PhpSpreadsheet\Writer;
use yii\db\ActiveRecord;

/**
 * @property int $departure_city Id города отправления
 * @property int $arrival_city Id города прибытия
 * @property int $distance_by_roads Расстояние между городами по дорогам
 * @property int $distance_by_air Расстояние между городами по воздуху (по прямой)
 */
class Routes extends ActiveRecord
{
    /**
     * Получает массив данных по направлениям в формате:
     * "id направления" => ["Название направления", "Код направления"]
     *
     * @return array<int,array<string,string>>
     */
    public static function getRoutesData()
    {
        $routes_data = [];
        $routes = static::find()
            ->all();
        $citys = Citys::find()
            ->where(['tracked' => true])
            ->all();
        $citys_ids_names_codes = [];

        foreach ($citys as $city) {
            $citys_ids_names_codes[ $city->id ] = [
                'name' => $city->name,
                'code' => $city->city_code,
            ];
        }

        foreach ($routes as $route) {
            $departure_city = $citys_ids_names_codes[ $route->departure_city ];
            $arrival_city   = $citys_ids_names_codes[ $route->arrival_city ];

            $route_name = $departure_city['name'] . ' - ' . $arrival_city['name'];
            $route_code = $departure_city['code'] . '_' . $arrival_city['code'];

            $routes_data[ $route->id ] = [
                'route_name' => $route_name,
                'route_code' => $route_code,
                'departure_city_id' => $route->departure_city,
                'arrival_city_id' => $route->arrival_city,
            ];
        }

        return $routes_data;
    }

    /**
     * Получает общую статистику по всем направлениям
     *
     * @return array
     */
    public static function getStatisticsData()
    {
        $all_validities = Statistics::getAllRoutesValidities();
        $routes_codes_names = static::getRoutesData();

        $flights_count_all_days = static::get3dArray(static::getFlightsCountAllDays());
        $flights_count_today = static::get3dArray(static::getFlightsCountToday());
        $trips_count_all_days = static::get3dArray(static::getTripsCountAllDays());
        $trips_count_today = static::get3dArray(static::getTripsCountToday());
        $invalid_tours_count_all = static::get3dArray(static::getAllInvalidTours());

        $tours_count = array_merge_recursive(
            $flights_count_all_days,
            $flights_count_today,
            $trips_count_all_days,
            $trips_count_today,
            $invalid_tours_count_all
        );

        foreach ($routes_codes_names as $id => $route) {
            $route_code = $route['route_code'];
            $count_data = array_merge_recursive(
                $tours_count[ $route_code ] ?? [],
                [
                    'Y' => [],
                    'C' => [],
                    'BUS' => [],
                ]
            );

            $all_count = 0;

            if (!empty($tours_count[ $route_code ])) {
                foreach ($tours_count[ $route_code ] as $counts) {
                    foreach ($counts as $tour_type => $count) {
                        $all_count += (int) $count;
                    }
                }
            }

            $validities_list = '';

            if (!empty($all_validities[ $route_code ])) {
                $validities_list = implode(' ', $all_validities[ $route_code ]);
            }

            $routes_data[ $id ] = [
                'route_name' => $route['route_name'],
                'route_code' => $route_code,
                'departure_city_id' => $route['departure_city_id'],
                'arrival_city_id' => $route['arrival_city_id'],
                'all_count' => $all_count,
                'validities' => $validities_list,
                'count_data' => $count_data,
            ];
        }

        return $routes_data;
    }

    /**
     * Получает количество дат с авиарейсами за все дни, начиная с сегодняшнего
     *
     * @return array
     */
    public static function getFlightsCountAllDays()
    {
        return \Yii::$app->db
            ->createCommand(
                'SELECT DISTINCT `route_code`, `service_class` `tour_type`, COUNT(`date`) `count_all_days`
                FROM `flights`
                WHERE `date` >= CURRENT_DATE
                AND `is_deleted` = false
                GROUP BY route_code, service_class'
            )
            ->queryAll();
    }

    /**
     * Получает количество сегодняшних авиарейсов
     *
     * @return array
     */
    public static function getFlightsCountToday()
    {
        return \Yii::$app->db
            ->createCommand(
                'SELECT DISTINCT `route_code`, `service_class` `tour_type`, COUNT(`date`) `count_today`
                FROM `flights`
                WHERE `date` >= CURRENT_DATE
                AND `is_deleted` = false
                AND DATE(`collected_at_gmt`) = CURRENT_DATE
                GROUP BY route_code, service_class'
            )
            ->queryAll();
    }

    /**
     * Получает количество дат с автобусных рейсов за все дни, начиная с сегодняшнего
     *
     * @return array
     */
    public static function getTripsCountAllDays()
    {
        return \Yii::$app->db
            ->createCommand(
                "SELECT DISTINCT `route_code`,'BUS' as `tour_type`, COUNT(`departure_time`) `count_all_days`
                FROM `trips`
                WHERE DATE(`departure_time`) >= CURRENT_DATE
                AND `is_deleted` = false
                GROUP BY route_code"
            )
            ->queryAll();
    }


    /**
     * Получает количество сегодняшних автобусных рейсов
     *
     * @return array
     */
    public static function getTripsCountToday()
    {
        return \Yii::$app->db
            ->createCommand(
                "SELECT DISTINCT `route_code`, 'BUS' as `tour_type`, COUNT(`departure_time`) `count_today`
                FROM `trips`
                WHERE DATE(`departure_time`) >= CURRENT_DATE
                AND `is_deleted` = false
                AND DATE(`collected_at_gmt`) = CURRENT_DATE
                GROUP BY route_code"
            )
            ->queryAll();
    }


    /**
     * Получает количество невалидных рейсов за все дни, начиная с сегодняшнего
     *
     * @return array
     */
    public static function getAllInvalidTours()
    {
        $last_create_task = ToursReports::getLastTask(['create'], 'start_time');

        if (
            empty($last_create_task)
            || date('Y-m-d') > date('Y-m-d', strtotime($last_create_task->start_time))
            || empty($last_create_task->parse_number)
        ) {
            return [];
        }

        return \Yii::$app->db
            ->createCommand(
                "SELECT `route_code`, `tour_type`, SUM(`errors_count`) `count_invalid`
                FROM `invalid_tours`
                WHERE `parse_number` >= $last_create_task->parse_number
                AND `date` >= CURRENT_DATE
                GROUP BY `route_code`, `tour_type`"
            )
            ->queryAll();
    }

    /**
     * Получает из одномерного массива полей и их значений массив вида:
     * ["Код направления" => ["Тип рейса" => ["Кол-во за сегодня", "Кол-во за все дни", "Кол-во ошибок", и т.д.]]]
     *
     * @param array $array_1d
     * @return array<string,array<string,array<string,string|int>>>
     */
    private static function get3dArray(array $array_1d): array
    {
        $array_3d = [];

        foreach ($array_1d as $i => $tours_count) {
            $route_code = (string) $tours_count['route_code'];
            $tour_type = $tours_count['tour_type'];
            $count = end($tours_count);
            $count_label = key($tours_count);

            $array_3d[ (string) $route_code ][ $tour_type ][ $count_label ] = $count;
        }

        return $array_3d;
    }

    public static function getLastCollectedGmtTrip(string $route_code)
    {
        return Statistics::getLastCollectedGmtTrip(null, $route_code);
    }

    public static function getLastCollectedGmtFlight(string $route_code, string $service_class)
    {
        return Statistics::getLastCollectedGmtFlight(null, $route_code, $service_class);
    }

    public static function getListValidities(string $tour_type, string $route_code)
    {
        return Statistics::getListValidities($tour_type, $route_code);
    }

    /**
     * Получает коды городов из кода направления
     *
     * @param string $route_code
     * @return string[]
     */
    public static function getCitysCodes(string $route_code)
    {
        //кодГородаОтправления_кодГородаПрибытия, код города - цифры и буквы
        preg_match('/^([^_]+)_([^_]+)$/', $route_code, $route_codes);

        $route_from_code = $route_codes[1];
        $route_to_code   = $route_codes[2];

        return [
            $route_from_code,
            $route_to_code,
        ];
    }

    /**
     * Получает код направления по ID городов отправления и прибытия
     *
     * @param integer $departure_city_id
     * @param integer $arrival_city_id
     * @return string
     */
    public static function getRouteCode(int $departure_city_id, int $arrival_city_id): string
    {
        $departure_city = Citys::findOne($departure_city_id);
        $arrival_city = Citys::findOne($arrival_city_id);

        return $departure_city->city_code . '_' . $arrival_city->city_code;
    }

    /**
     * Получает направление по кодам городов отправления и прибытия
     *
     * @param string $route_from_code
     * @param string $route_to_code
     * @return Routes|null
     */
    public static function getRouteByCitysCodes(string $route_from_code, string $route_to_code)
    {
        $departure_city = Citys::findOne(['city_code' => $route_from_code]);
        $arrival_city = Citys::findOne(['city_code' => $route_to_code]);

        return static::findOne([
            'departure_city' => $departure_city->id,
            'arrival_city' => $arrival_city->id,
        ]);
    }

    public static function getDepartureAndArrivalCitys(): array
    {
        $routes = Routes::find()->all();
        $citys = Citys::find()->all();

        $departure_citys_id = [];
        $arrival_citys_id = [];

        foreach ($routes as $route) {
            $departure_citys_id[] = $route->departure_city;
            $arrival_citys_id[] = $route->arrival_city;
        }

        $departure_citys = array_flip(array_unique($departure_citys_id));
        $arrival_citys = array_flip(array_unique($arrival_citys_id));

        foreach ($citys as $city) {
            if (array_key_exists($city->id, $departure_citys)) {
                $departure_citys[ $city->id ] = $city->name;
            }

            if (array_key_exists($city->id, $arrival_citys)) {
                $arrival_citys[ $city->id ] = $city->name;
            }
        }

        return [
            'departure_citys' => $departure_citys,
            'arrival_citys' => $arrival_citys,
        ];
    }

    /**
     * @param integer $route_id
     * @return array<string,array<string,mixed>>
     */
    public static function getDetailByRoute(int $route_id)
    {
        $route = Routes::findOne($route_id);
        $route_code = Routes::getRouteCode($route->departure_city, $route->arrival_city);

        $tours_types = Tours::getToursTypesList();
        $details = [];

        foreach ($tours_types as $tour_type) {
            $arr = [];
            $arr['count-all-dates'] = Statistics::countDatesTours($tour_type, $route_code);
            $arr['count-target-dates'] = Statistics::countDatesTours($tour_type, $route_code, true);
            $arr['validities'] = Routes::getListValidities($tour_type, $route_code);

            $details[ $tour_type ] = $arr;
        }

        $details['BUS']['distance'] = $route->distance_by_roads;
        $details['C']['distance'] = $route->distance_by_air;
        $details['Y']['distance'] = $route->distance_by_air;

        $details['BUS']['collected-gmt'] = Routes::getLastCollectedGmtTrip($route_code);
        $details['C']['collected-gmt'] = Routes::getLastCollectedGmtFlight($route_code, 'C');
        $details['Y']['collected-gmt'] = Routes::getLastCollectedGmtFlight($route_code, 'Y');

        return $details;
    }


    public static function createRoutesXlsx(string $filepath)
    {
        $routes_data = static::getStatisticsData();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        /**
         * Задаются значения
         */
        $sheet->setCellValue('A1', 'Направление');
        $sheet->setCellValue('B1', 'Код направления');
        $sheet->setCellValue('C1', 'А-Э вс./сег.');
        $sheet->setCellValue('D1', 'А-Б вс./сег.');
        $sheet->setCellValue('E1', 'АВТО вс./сег.');

        $tours_types = Tours::getToursTypesList();
        $row_number = 1;

        foreach ($routes_data as $route_id => $route_data) {
            $row_number += 1;

            foreach ($tours_types as $tour_type) {
                $count_all_days[ $tour_type ] = $route_data['count_data'][ $tour_type ]['count_all_days'] ?? 0;
                $count_today[ $tour_type ] = $route_data['count_data'][ $tour_type ]['count_today'] ?? 0;
                $count_invalid[ $tour_type ] = $route_data['count_data'][ $tour_type ]['count_invalid'] ?? 0;

                $colunm[ $tour_type ]  = $count_all_days[ $tour_type ];
                $colunm[ $tour_type ] .= ' / ' . $count_today[ $tour_type ];
                $colunm[ $tour_type ] .= ' (' . $count_invalid[ $tour_type ] . ')';
            }

            $sheet->setCellValue('A' . $row_number, (string) $route_data['route_name']);
            $sheet->setCellValue('B' . $row_number, (string) $route_data['route_code']);
            $sheet->setCellValue('C' . $row_number, (string) $colunm['Y']);
            $sheet->setCellValue('D' . $row_number, (string) $colunm['C']);
            $sheet->setCellValue('E' . $row_number, (string) $colunm['BUS']);
        }

        /**
         * Задаются стили
         */

        $common_styles = [
            'font' => [
                'name' => 'Times New Roman',
                'size' => 10,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ],
            ],
        ];
        $header_style = [
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'vertical' => Style\Alignment::VERTICAL_TOP,
                'horizontal' => Style\Alignment::HORIZONTAL_CENTER,
            ],
        ];
        $body_style = [
            'alignment' => [
                'horizontal' => Style\Alignment::HORIZONTAL_LEFT,
            ],
        ];

        $sheet->getStyle('A1:E1')->applyFromArray(array_replace_recursive(
            $common_styles,
            $header_style
        ));
        $sheet->getStyle('A2:E' . $row_number)->applyFromArray(array_replace_recursive(
            $common_styles,
            $body_style
        ));

        $sheet->getRowDimension(1)->setRowHeight(25);

        $sheet->getColumnDimension('A')->setWidth(40);
        $sheet->getColumnDimension('B')->setWidth(24);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(20);

        $sheet->getStyleByColumnAndRow(1, 2, 5, $row_number)
            ->getNumberFormat()
            ->setFormatCode(Style\NumberFormat::FORMAT_TEXT);

        $writer = new Writer\Xlsx($spreadsheet);
        $writer->save($filepath);
    }
}
