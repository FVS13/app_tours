<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\Routes;
use app\models\Statistics;

class RoutesController extends Controller
{
    public $layout = 'tours-admin';
    public $defaultAction = 'default';

    public function actionDefault()
    {
        $routes_data = \Yii::$app->cache->getOrSet('routes-statistics', function () {
            return Routes::getStatisticsData();
        }, 60 * 60 * 3);

        $citys = Routes::getDepartureAndArrivalCitys();
        $validities = Statistics::getAllValidities();

        $departure_citys = $citys['departure_citys'];
        $arrival_citys = $citys['arrival_citys'];

        asort($departure_citys);
        asort($arrival_citys);
        asort($validities);

        return $this->render('@app/views/tours-admin/routes', [
            'routes_data' => $routes_data,
            'departure_citys' => $departure_citys,
            'arrival_citys' => $arrival_citys,
            'validities' => $validities,
        ]);
    }

    public function actionGetDetailByRoute()
    {
        $route_id = (int) Yii::$app->request->get('route_id');

        $details = Routes::getDetailByRoute($route_id);

        return $this->asJson(['details' => $details]);
    }

    public function actionGetRoutesXlsx()
    {
        $webroot_path = \Yii::getAlias('@webroot');
        $routes_xlsx_path = "${webroot_path}/routes.xlsx";

        Routes::createRoutesXlsx($routes_xlsx_path);

        return \Yii::$app->response->sendFile($routes_xlsx_path);
    }
}
