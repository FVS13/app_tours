<?php

namespace app\commands;

use app\common\controllers\ErrorHandlerController;
use app\models\AircraftReference;
use app\models\Airlines;
use app\models\Airports;
use Yii;
use yii\console\Controller;
use app\models\Citys;
use app\models\ExportTours;
use app\models\IcaoCodes;
use app\models\Routes;
use app\models\Settings;
use app\models\Statistics;
use app\models\TariffsNames;
use app\models\ToursReports;
use app\models\Tours;

class ToursController extends Controller
{
    public $layout = 'tours-admin';
    public const EXISTS_STARTED_TASK_MESSAGE = 'Уже есть запущенная задача' . PHP_EOL;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
    }

    public function actionParse(bool $is_auto = false)
    {
        if (
            ToursReports::existsStartedTask()
            || $is_auto && ! Settings::getState('auto_parse')
        ) {
            echo static::EXISTS_STARTED_TASK_MESSAGE;
            return;
        }

        $last_update = ToursReports::getLastTask(['create', 'update'], 'start_time');
        $report = ToursReports::start('create');

        $this->cacheInit([
            Citys::class,
            Airlines::class,
        ]);

        TariffsNames::initCache();

        if (empty($last_update)) {
            $duration = 2 * 24 * 60 * 60;
        } else {
            $duration = \strtotime($last_update->start_time);
        }

        $time_diff = \time() - $duration;
        $mmin = (int) ($time_diff / 60) + 1;

        $this->initJsonFiles($mmin);

        try {
            $root = Yii::$app->params['incoming_data_path'];

            $report->changeStatus('processing');
            $report->target = shell_exec(
                "cd $root && find -type f | grep -v '.*/.*/.*/.*/_' | xargs -L 100 grep validity | wc -l"
            );

            $tours_parser = new Tours();
            $tours_parser->on(Tours::EVENT_PROGRESS, [$report, 'updateProgress']);
            $tours_parser->parse($root, $report->parse_number);

            IcaoCodes::complete();
        } catch (\Throwable $th) {
            $report->end('error');
            throw $th;
        }

        $report->end('finished');
    }

    public function actionExport(bool $is_auto = false)
    {
        if (ToursReports::existsStartedTask()) {
            echo static::EXISTS_STARTED_TASK_MESSAGE;
            return static::EXISTS_STARTED_TASK_MESSAGE;
        }

        $settings = Settings::find()->all();
        $settings = array_column($settings, 'state', 'name');

        if ($is_auto && ! $settings['auto_export']) {
            return;
        }

        $report = ToursReports::start('export');

        $this->cacheInit([
            Citys::class,
            Airlines::class,
            IcaoCodes::class,
            AircraftReference::class
        ]);

        TariffsNames::initCache();
        Airports::initCache([], ['locationCity.airport']);

        try {
            Tours::removeOlderTours(2);
            $exporter = new ExportTours(\Yii::$app->params['export_dir']);

            $error =  $exporter->validate();

            if ('' !== $error) {
                \Yii::error($error);
                $report->end('error');
                return;
            }

            $report->changeStatus('processing');
            $exporter->on(ExportTours::EVENT_PROGRESS, [$report, 'updateProgress']);

            if ($settings['create_xslx']) {
                $exporter->createMinmaxFlightPriceFile('minmax');
            }

            $exporter->removeJsons('jsons');
            $exporter->exportToJsons('jsons');

            if ($settings['create_archive']) {
                $exporter->createArchive('jsons', 'archives');
            }
        } catch (\Throwable $th) {
            $report->end('error');
            throw $th;
        }

        $report->end('finished');
    }

    private function initJsonFiles(int $mmin)
    {
        $losg_path = \Yii::getAlias('@runtime/logs');
        $shell_scripts_dir = \Yii::$app->params['shell_scripts_dir'];

        $mmin = abs($mmin);
        $data_path = realpath(\Yii::$app->params['data_path']);
        $incoming_data_path = realpath(\Yii::$app->params['incoming_data_path']);
        $parsers_server = \Yii::$app->params['parsers_server'];

        $path_to_init_parse_sh = realpath($shell_scripts_dir . '/init_parse.sh');
        $path_to_init_parse_with_remote_sh = realpath($shell_scripts_dir . '/init_parse_with_remote.sh');

        shell_exec("$path_to_init_parse_with_remote_sh $data_path $parsers_server $losg_path");
        shell_exec("$path_to_init_parse_sh $mmin $data_path $incoming_data_path");
    }

    private function initIcaosFiles()
    {
        $webroot_path = \Yii::getAlias('@webroot');
        $shell_scripts_path = \Yii::$app->params['shell_scripts_dir'];
        $parsers_server = \Yii::$app->params['parsers_server'];

        $path_to_init_parse_sh = realpath("$shell_scripts_path/init_icao_with_remote.sh $webroot_path $parsers_server");

        shell_exec("$path_to_init_parse_sh");
    }

    public function actionErrorsHandler()
    {
        $exception = Yii::$app->getErrorHandler()->exception;

        (new ErrorHandlerController())->renderException($exception);
    }

    public function actionHaltTasks()
    {
        $faulty_tasks = ToursReports::find()
            ->where(['end_time' => null])
            ->all();

        foreach ($faulty_tasks as $task) {
            $task->end('stopped');
        }
    }

    public function actionUpdateCache()
    {
        $cache_lifetime = 60 * 60 * 3;
        $view_data_statistics = Statistics::getViewDataStatistics();
        $routes_statistics = Routes::getStatisticsData();

        \Yii::$app->cache->set('view-data-statistics', $view_data_statistics, $cache_lifetime);
        \Yii::$app->cache->set('routes-statistics', $routes_statistics, $cache_lifetime);
    }

    public function actionExportIcaos(bool $all_nulls_icaos = false, string $format_name = 'ozon')
    {
        if (ToursReports::existsStartedTask()) {
            echo static::EXISTS_STARTED_TASK_MESSAGE;
            return static::EXISTS_STARTED_TASK_MESSAGE;
        }

        $report = ToursReports::start('export_icaos_' . $format_name);

        $this->cacheInit([Airlines::class]);

        try {
            $webroot_path = \Yii::getAlias('@webroot');
            $file_path = "${webroot_path}/icaos-${format_name}.json";

            $report->changeStatus('processing');
            IcaoCodes::exportIcaos($file_path, $format_name, $all_nulls_icaos);
            $this->initIcaosFiles();
        } catch (\Throwable $th) {
            $report->end('error');
            Yii::error('actionExportIcaos(): фатальная ошибка');
            Yii::error($th);

            $report->end('fatal-error');

            throw $th;
        }

        $report->end('finished');
    }

    public function actionImportIcaos(string $format_name = 'ozon')
    {
        if (ToursReports::existsStartedTask()) {
            echo static::EXISTS_STARTED_TASK_MESSAGE;
            return static::EXISTS_STARTED_TASK_MESSAGE;
        }

        $this->initIcaosFiles();

        $report = ToursReports::start('import_icaos_' . $format_name);

        try {
            $webroot_path = \Yii::getAlias('@webroot');
            $file_path = "${webroot_path}/icaos-${format_name}-out.json";

            if (!file_exists($file_path)) {
                return "Файл не найден: $file_path";
            }

            $new_icaos = json_decode(file_get_contents($file_path), true);
            $updated_at_unixtime = filemtime($file_path);
            $updated_at_datetime = date('Y-m-d H:i:s', $updated_at_unixtime);

            IcaoCodes::updateAll(
                ['updated_at' => $updated_at_datetime],
                [
                    'and',
                    ['icao' => null],
                ]
            );

            $report->changeStatus('processing');

            foreach ($new_icaos as $new_icao) {
                $date = $new_icao['date'];

                if ('seatguru' === $format_name) {
                    $date .= ' ' . $new_icao['time'] . 'm. ';
                }

                $cond = [
                    'flight_num' => $new_icao['flight_num'],
                    'airline_code' => $new_icao['flight_code'],
                    'date' => date('Y-m-d H:i:s', strtotime($date)),
                ];

                $icao_code = IcaoCodes::findOne($cond);

                if (empty($icao_code)) {
                    // Yii::error('Найден ICAO для лишнего рейса');
                    // Yii::error($cond);
                    continue;
                }

                $icao_code->icao = $new_icao['ICAO'] ?? null;
                $icao_code->updated_at = $updated_at_datetime;

                try {
                    $icao_code->save();
                } catch (\Throwable $th) {
                    Yii::error('actionImportIcaos(): ошибка сохранения ICAO');
                    Yii::error($th);
                }
            }
        } catch (\Throwable $th) {
            Yii::error('actionImportIcaos(): фатальная ошибка');
            Yii::error($th);

            $report->end('fatal-error');

            throw $th;
        }

        $report->end('finished');
    }

    /**
     * @param string[] $classes
     * @return void
     */
    private function cacheInit(array $classes = [])
    {
        foreach ($classes as $class) {
            $class::cacheInit();
        }
    }
}
