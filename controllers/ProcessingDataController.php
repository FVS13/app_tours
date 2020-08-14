<?php

namespace app\controllers;

use app\models\Settings;
use app\models\ToursReports;
use yii\web\Controller;
use yii\web\MethodNotAllowedHttpException;

class ProcessingDataController extends Controller
{
    public $layout = 'tours-admin';
    public $defaultAction = 'default';

    public function actionDefault()
    {
        $settings = Settings::find()->all();
        $settings = array_column($settings, 'state', 'name');

        $last_parse = ToursReports::getLastTask(['create', 'update']);
        $last_export = ToursReports::getLastTask(['export']);
        $exists_started_task = ToursReports::existsStartedTask();

        $export_icaos_ozon = ToursReports::getLastTask(['export_icaos_ozon']);
        $import_icaos_ozon = ToursReports::getLastTask(['import_icaos_ozon']);
        $export_icaos_seatguru = ToursReports::getLastTask(['export_icaos_seatguru']);
        $import_icaos_seatguru = ToursReports::getLastTask(['import_icaos_seatguru']);

        return $this->render('@app/views/tours-admin/processing-data', [
            'settings' => $settings,
            'last_parse' => $last_parse,
            'last_export' => $last_export,
            'exists_started_task' => $exists_started_task,
            'export_icaos_ozon' => $export_icaos_ozon,
            'import_icaos_ozon' => $import_icaos_ozon,
            'export_icaos_seatguru' => $export_icaos_seatguru,
            'import_icaos_seatguru' => $import_icaos_seatguru,
        ]);
    }

    public function actionParse()
    {
        if (!\Yii::$app->request->isPost) {
            throw new MethodNotAllowedHttpException();
        }

        if (ToursReports::existsStartedTask()) {
            return 'Уже есть запущенная задача';
        }

        $app_path = \Yii::getAlias('@app');

        exec($app_path . '/bilet_apptours tours/parse > /dev/null &');

        return 'Сбор в базу запущен';
    }

    public function actionExport()
    {
        if (!\Yii::$app->request->isPost) {
            throw new MethodNotAllowedHttpException();
        }

        if (ToursReports::existsStartedTask()) {
            return 'Уже есть запущенная задача';
        }

        $app_path = \Yii::getAlias('@app');

        exec($app_path . '/bilet_apptours tours/export > /dev/null &');

        return 'Выгрузка запущена';
    }

    public function actionChangeSetting()
    {
        if (!\Yii::$app->request->isPost) {
            throw new MethodNotAllowedHttpException();
        }

        $setting_name = \Yii::$app->request->post('setting_name');
        $is_checked = 'true' === \Yii::$app->request->post('is_checked');

        $res = 'Сохранение прошло успешно';

        if (!Settings::setState($setting_name, $is_checked)) {
            $res = 'Ошибка изменения настройки';
        }

        return $res;
    }

    public function actionHaltTasks()
    {
        $app_path = \Yii::getAlias('@app');
        $shell_scripts_path = \Yii::$app->params['shell_scripts_dir'];

        exec("nohup ${shell_scripts_path}/halt_tasks.sh ${app_path}");

        return 'Задачи остановлены';
    }

    public function actionExportIcaos()
    {
        if (!\Yii::$app->request->isPost) {
            throw new MethodNotAllowedHttpException();
        }

        if (ToursReports::existsStartedTask()) {
            return 'Уже есть запущенная задача';
        }

        $format_name = \Yii::$app->request->post('format_name');
        $all_icaos = Settings::getState('export_all_icaos_' . $format_name) ?? 0;

        $app_path = \Yii::getAlias('@app');

        exec("${app_path}/bilet_apptours tours/export-icaos $all_icaos $format_name > /dev/null &");

        return "Выгрузка icao для $format_name запущена";
    }

    public function actionImportIcaos()
    {
        if (!\Yii::$app->request->isPost) {
            throw new MethodNotAllowedHttpException();
        }

        if (ToursReports::existsStartedTask()) {
            return 'Уже есть запущенная задача';
        }

        $format_name = \Yii::$app->request->post('format_name');

        $app_path = \Yii::getAlias('@app');

        exec("$app_path/bilet_apptours tours/import-icaos $format_name > /dev/null &");

        return "Импорт icao для $format_name запущен";
    }
}
