<?php

namespace app\common\controllers;

use app\models\ToursReports;
use yii\console\ErrorHandler;

class ErrorHandlerController extends ErrorHandler
{
    public function renderException($exception)
    {
        \Yii::error($exception);

        \Yii::$app->db->close();
        \Yii::$app->db->open();

        $current_report = ToursReports::findOne(['id' => ToursReports::$current_report_id]);

        if (empty($current_report)) {
            \Yii::error('Не обработанная ошибка; никакая задача не запущена');
            return;
        }

        $current_report->end('fatal-error');
    }
}
