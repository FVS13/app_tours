<?php

namespace app\controllers;

use yii\web\Controller;
use app\models\Statistics;

class ViewDataController extends Controller
{
    public $layout = 'tours-admin';
    public $defaultAction = 'default';

    public function actionDefault()
    {
        $statistics = \Yii::$app->cache->getOrSet('view-data-statistics', function () {
            return Statistics::getViewDataStatistics();
        }, 60 * 60 * 3);

        return $this->render('@app/views/tours-admin/view-data', [
            'statistics' => $statistics,
        ]);
    }
}
