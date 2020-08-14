<?php

namespace app\models;

use yii\db\Expression;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style;
use PhpOffice\PhpSpreadsheet\Writer;
use yii\base\ActionEvent;
use yii\base\Component;
use yii\base\Exception;

class ExportTours extends Component
{
    public const EVENT_PROGRESS = 'progress';

    private $export_dir;
    public static $jsons_format = JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;

    private $routes_data;
    private $routes_codes;
    private $tours_types;
    private $target_dates;

    public function __construct(string $export_dir)
    {
        $this->export_dir = \realpath($export_dir);

        $this->routes_data = Routes::getRoutesData();
        $this->routes_codes = array_column($this->routes_data, 'route_code');
        $this->tours_types = Tours::getToursTypesList();
        $this->target_dates = Tours::getTargetDates();
    }

    public function validate(): string
    {
        if (false === $this->export_dir || !file_exists($this->export_dir)) {
            return 'Директория для экспорта не существует';
        }

        if (!is_dir($this->export_dir)) {
            return 'Директория для экспорта не является директорией';
        }

        if (!is_writable($this->export_dir)) {
            return 'Директория для экспорта недоступна для записи';
        }

        return '';
    }

    public function createMinmaxFlightPriceFile(string $subdir)
    {
        $minmax_file_name = 'minmax_fly_' . date('Y-m-d') . '.xlsx';
        $minmax_file_path = implode('/', [$this->export_dir, $subdir, $minmax_file_name]);

        $minmax_data_arr = static::getMinmaxData();
        $minmax_table = static::createMinmaxExcelTable($minmax_data_arr);

        $writer = new Writer\Xlsx($minmax_table);
        $writer->save($minmax_file_path);
    }

    private static function getMinmaxData(): array
    {
        $minmax_data_arr = \Yii::$app->db
            ->createCommand(
                "SELECT
                    `flights`.`route_code`,
                    `flights`.`service_class`,
                    MIN(`flights_tariffs_info`.`price`) `min_price`,
                    MAX(`flights_tariffs_info`.`price`) `max_price`
                FROM `flights`
                INNER JOIN `flights_tariffs_info`
                ON `flights`.`is_deleted` = FALSE
                AND `flights`.`id` = `flights_tariffs_info`.`flight_ref`
                GROUP BY `flights`.`route_code`, `flights`.`service_class`"
            )
            ->queryAll();

        $routes_codes_names = array_column(Routes::getRoutesData(), 'route_name', 'route_code');
        asort($routes_codes_names);

        $minmax_data_by_routes = [];

        foreach ($routes_codes_names as $route_code => $route_name) {
            $minmax_data_by_routes[ $route_code ]['route_name'] = $route_name;
            $minmax_data_by_routes[ $route_code ]['air_service'] = 'Нет';
            $minmax_data_by_routes[ $route_code ]['Y']['min'] = '-';
            $minmax_data_by_routes[ $route_code ]['Y']['max'] = '-';
            $minmax_data_by_routes[ $route_code ]['C']['min'] = '-';
            $minmax_data_by_routes[ $route_code ]['C']['max'] = '-';
        }

        foreach ($minmax_data_arr as $minmax_data) {
            $route_code = $minmax_data['route_code'];
            $service_class = $minmax_data['service_class'];
            $min_price = $minmax_data['min_price'];
            $max_price = $minmax_data['max_price'];

            $minmax_data_by_routes[ $route_code ]['air_service'] = 'Есть';
            $minmax_data_by_routes[ $route_code ][ $service_class ]['min'] = $min_price;
            $minmax_data_by_routes[ $route_code ][ $service_class ]['max'] = $max_price;
        }

        return $minmax_data_by_routes;
    }

    private static function createMinmaxExcelTable(array $minmax_data_arr)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        /**
         * Задаются значения
         */
        $sheet->setCellValue('A1', 'Направление');
        $sheet->setCellValue('B1', 'Код направления');
        $sheet->setCellValue('C1', 'Авиасообщение');
        $sheet->setCellValue('D1', 'Эконом-класс. Минимальный');
        $sheet->setCellValue('E1', 'Эконом-класс. Максимальный');
        $sheet->setCellValue('F1', 'Бизнес-класс. Минимальный');
        $sheet->setCellValue('G1', 'Бизнес-класс. Максимальный');

        $row_number = 1;

        foreach ($minmax_data_arr as $route_code => $minmax_data) {
            $row_number += 1;

            $route_name = $minmax_data['route_name'];
            $air_service = $minmax_data['air_service'];
            $Y_min_price = $minmax_data['Y']['min'];
            $Y_max_price = $minmax_data['Y']['max'];
            $C_min_price = $minmax_data['C']['min'];
            $C_max_price = $minmax_data['C']['max'];

            $sheet->setCellValue('A' . $row_number, (string) $route_name);
            $sheet->setCellValue('B' . $row_number, (string) $route_code);
            $sheet->setCellValue('C' . $row_number, (string) $air_service);
            $sheet->setCellValue('D' . $row_number, (string) $Y_min_price);
            $sheet->setCellValue('E' . $row_number, (string) $Y_max_price);
            $sheet->setCellValue('F' . $row_number, (string) $C_min_price);
            $sheet->setCellValue('G' . $row_number, (string) $C_max_price);
        }

        /**
         * Задаются стили
         */
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => [
                'name' => 'Times New Roman',
                'bold' => true,
                'size' => 10,
            ],
            'alignment' => [
                'wrapText' => true,
                'vertical' => Style\Alignment::VERTICAL_TOP,
                'horizontal' => Style\Alignment::HORIZONTAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
        $sheet->getStyle('A2:G2000')->applyFromArray([
            'font' => [
                'name' => 'Times New Roman',
                'size' => 10,
            ],
            'alignment' => [
                'wrapText' => true,
                'horizontal' => Style\Alignment::HORIZONTAL_LEFT,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $sheet->getColumnDimension('A')->setWidth(40);
        $sheet->getColumnDimension('B')->setWidth(24);
        $sheet->getColumnDimension('C')->setWidth(17);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(16);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(16);

        $sheet->getStyleByColumnAndRow(1, 2, 7, $row_number)
            ->getNumberFormat()
            ->setFormatCode(Style\NumberFormat::FORMAT_TEXT);

        return $spreadsheet;
    }

    public function exportToJsons(string $subdir)
    {
        // При перегруженном сервере запрос на создание файла может придти перед запросом на создание его папки
        $this->createDirectoryTree($this->routes_codes, $this->tours_types, $subdir);

        $exported_tours = 0;
        $total_tours = $this->countExportingTours();
        $progress_event = new ActionEvent(static::EVENT_PROGRESS);
        $progress_event->result = ['target' => $total_tours];
        $this->trigger(static::EVENT_PROGRESS, $progress_event);

        foreach ($this->routes_data as $route_data) {
            $route_code = $route_data['route_code'];

            foreach ($this->tours_types as $tour_type) {
                $current_dir = implode('/', [$this->export_dir, $subdir, $route_code, $tour_type]) . '/';
                $is_null_tours = true;

                foreach ($this->target_dates as $date) {
                    $tours = static::getTours($route_code, $tour_type, $date);

                    if (empty($tours)) {
                        continue;
                    }

                    try {
                        $tours_file_content = static::getJsonFromTours($tour_type, $tours);
                        static::createToursFile($current_dir, $date, $tours_file_content);

                        $is_null_tours = false;
                        $exported_tours += count($tours);
                    } catch (\Throwable $th) {
                        \Yii::error($th);
                    }
                }

                if ($is_null_tours) {
                    static::createNullFile($current_dir, $route_data);
                }
            }

            $progress_event->result = ['progress' => $exported_tours];
            $this->trigger(static::EVENT_PROGRESS, $progress_event);
        }
    }

    public function createDirectoryTree(array $routes_codes, array $tours_types, string $root_dir)
    {
        foreach ($routes_codes as $route_code) {
            foreach ($tours_types as $tour_type) {
                $current_dir = implode('/', [$this->export_dir, $root_dir, $route_code, $tour_type]) . '/';

                if (!\file_exists($current_dir)) {
                    mkdir($current_dir, 0755, true);
                }
            }
        }
    }

    /**
     * Для очистки от результатов предыдущей выгрузки
     */
    public function removeJsons(string $subdir)
    {
        shell_exec('rm -R ' . $this->export_dir . '/' . $subdir);
    }

    private static function getTours(string $route_code, string $tour_type, string $date): array
    {
        $cond = [
            'and',
            ['is_deleted' => false],
            ['route_code' => $route_code],
        ];

        if ('BUS' !== $tour_type) {
            $cond[] = ['service_class' => $tour_type];
            $cond[] = ['date' => $date];
        } else {
            $cond[] = new Expression('DATE(`departure_time`) = :date', [':date' => $date]);
        }

        if ('BUS' !== $tour_type) {
            $tours = Flight::find()
                ->where($cond)
                ->with('segments', 'fares')
                ->all();
        } else {
            $tours = Trip::find()
                ->where($cond)
                ->all();
        }

        return $tours;
    }

    /**
     * @param string $tour_type
     * @param Flight[]|Trip[] $tours
     * @return string
     */
    private static function getJsonFromTours(string $tour_type, array $tours): string
    {
        $tours_arrs = [];

        $tour_type_label = ('BUS' === $tour_type) ?
            'автобусного рейса' :
            'авиа рейса' ;

        foreach ($tours as $tour) {
            try {
                $tour_as_array = $tour->getAsArray();
                $tour_as_array['collected_at_gmt'] = static::getExportTime();

                $tours_arrs[] = $tour_as_array;
            } catch (\Throwable $th) {
                \Yii::error("Ошибка выгрузки $tour_type_label с id: $tour->primaryKey");
                \Yii::error($th);
            }
        }

        if (empty($tours_arrs)) {
            throw new Exception('getJsonFromTours() нет валидных рейсов, нечего записывать в json');
        }

        return json_encode($tours_arrs, static::$jsons_format);
    }

    private static function createToursFile(string $current_dir, string $date, string $tours_file_content)
    {
        $file_path = "${current_dir}${date}.json";

        static::saveJsonFile($file_path, $tours_file_content);
    }

    private static function createNullFile(string $dir, array $route_data)
    {
        $departure_city_name = Citys::getById($route_data['departure_city_id'])->name;
        $arrival_city_name = Citys::getById($route_data['arrival_city_id'])->name;

        $null_data = (object) [
            'collected_at_gmt' => static::getExportTime(),
            'departure_city' => [
                'name' => $departure_city_name,
            ],
            'arrival_city' => [
                'name' => $arrival_city_name,
            ],
            'fares' => 0,
        ];

        $null_json = json_encode($null_data, static::$jsons_format);

        static::saveJsonFile($dir . 'null.json', $null_json);
    }

    private static function saveJsonFile(string $file_path, string $json)
    {
        try {
            \file_put_contents($file_path, $json);
        } catch (\Throwable $th) {
            \Yii::error($th);
            chmod($file_path, 0644);
            \file_put_contents($file_path, $json);
        }
    }

    public function createArchive(string $input_subdir, string $output_subdir)
    {
        $path_to_create_archive_sh = realpath(\Yii::$app->params['shell_scripts_dir'] . '/create_archive.sh');
        $archive_path = $output_subdir . '/archive_' . date('Y-m-d') . '.tgz';

        shell_exec("$path_to_create_archive_sh $this->export_dir $input_subdir $archive_path");
    }

    /**
     * @return integer
     */
    private function countExportingTours(): int
    {
        $count_flights = Flight::find()
            ->where([
                'and',
                ['is_deleted' => false],
                ['date' => $this->target_dates],
            ])
            ->count();

        $count_trips = Trip::find()
            ->where([
                'and',
                ['is_deleted' => false],
                ['DATE([[departure_time]])' => $this->target_dates],
            ])
            ->count();

        return $count_flights + $count_trips;
    }

    private static function getExportTime(): string
    {
        $export_time = date('Y-m-d H:i:s', time() - 60 * 60 * 11);
        $export_time = preg_replace('/ /', 'T', $export_time);

        return $export_time;
    }
}
