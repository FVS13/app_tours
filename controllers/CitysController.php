<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\Airports;
use app\models\AltCitysNames;
use app\models\CityForm;
use app\models\Citys;
use app\models\NewCitys;

/**
 * Методы, отвечающие за Добавление/Редактирование/Удаление
 * возвращают значения всех полей, которые могут(!) быть изменены
 * Это необходимо для отображения изменений без перезагрузки
 */
class CitysController extends Controller
{
    public $layout = 'tours-admin';
    public $defaultAction = 'default';
    private $post_data;
    private $city;
    private $airport;
    private $alt_names;
    private $binded_airport;
    private $alt_name;
    private $curr_city_id;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);

        $this->post_data = Yii::$app->request->post();
        $this->alt_name = $this->post_data['alt_name'] ?? '';
        $this->alt_name = trim($this->alt_name);
        $this->curr_city_id = $this->post_data['city_id'] ?? null;

        $this->city = $this->post_data['city'] ?? [];
        $this->airport = $this->post_data['airport'] ?? [];
        $this->alt_names = $this->post_data['alt_names'] ?? [];
        $this->binded_airport = $this->post_data['binded_airport'] ?? [];

        $this->alt_names = array_map('trim', $this->alt_names);
        $this->alt_names = array_filter($this->alt_names);
    }

    /**
     * Вывод раздел "Города"
     *
     * @return void
     */
    public function actionDefault()
    {
        $tracked_citys = Citys::find()
            ->where([
                'and',
                ['>', 'id', 0],
                ['tracked' => true],
            ])
            ->orderBy(['name' => SORT_ASC])
            ->with('airport', 'bindedAirport.airport.locationCity')
            ->all();

        $airports = Airports::find()
            ->joinWith('locationCity')
            ->with('locationCity.airport', 'locationCity.altNames')
            ->orderBy(['name' => SORT_ASC])
            ->all();

        $transit_citys = [];
        foreach ($airports as $airport) {
            $transit_citys[ $airport->locationCity->id ] = $airport->locationCity;
        }

        $new_names = NewCitys::find()->all();

        return $this->render('@app/views/tours-admin/citys', [
            'tracked_citys' => $tracked_citys,
            'transit_citys' => $transit_citys,
            'new_names' => $new_names,
        ]);
    }

    /**
     * Добавление нового аэропорта
     *
     * @return json ['city':[...],'airport':[...],'alt_names':[...]] | ['errors']
     *
     */
    public function actionAddNewTransitCity()
    {
        try {
            $city_form = new CityForm();
            $city_form->attributes = [
                'city' => $this->city,
                'airport' => $this->airport,
                'alt_names' => $this->alt_names,
            ];

            $result = $city_form->addNewTransitCity();
        } catch (\Throwable $th) {
            Yii::error($th);
            Yii::$app->response->statusCode = 500;
            return $this->asJson(['errors' => 'Произошла неизвестная ошибка']);
        }

        if ($city_form->hasErrors()) {
            Yii::$app->response->statusCode = 400;
            return $this->asJson(['errors' => $city_form->errors]);
        }

        Yii::$app->response->statusCode = 200;
        return $this->asJson($result);
    }

    /**
     * Редактирование аэропорта
     *
     * @return json ['city':[...],'airport':[...],'alt_names':[...]] | ['errors']
     */
    public function actionEditTransitCity()
    {
        try {
            $city_form = new CityForm();
            $city_form->attributes = [
                'city' => $this->city,
                'airport' => $this->airport,
                'alt_names' => $this->alt_names,
            ];

            $result = $city_form->editTransitCity();
        } catch (\Throwable $th) {
            Yii::error($th);
            Yii::$app->response->statusCode = 500;
            return $this->asJson(['errors' => 'Произошла неизвестная ошибка']);
        }

        if ($city_form->hasErrors()) {
            Yii::$app->response->statusCode = (false === $result) ? 500 : 400;
            return $this->asJson(['errors' => $city_form->errors]);
        }

        Yii::$app->response->statusCode = 200;
        return $this->asJson($result);
    }

    /**
     * Удаление аэропорта
     *
     * @return json ['city','airport'] | ['errors']
     */
    public function actionRemoveTransitCity()
    {
        try {
            $city_form = new CityForm();
            $city_form->attributes = [
                'city_id' => $this->curr_city_id,
            ];

            $result = $city_form->removeTransitCity();
        } catch (\Throwable $th) {
            Yii::error($th);
            Yii::$app->response->statusCode = 500;
            return $this->asJson(['errors' => 'Произошла неизвестная ошибка']);
        }

        if ($city_form->hasErrors()) {
            Yii::$app->response->statusCode = 400;
            return $this->asJson(['errors' => $city_form->errors]);
        }

        Yii::$app->response->statusCode = 200;
        return $this->asJson($result);
    }

    /**
     * Редактирование отслеживаемого города
     *
     * @return json ['city':[...],'airport':[...],'binded_airport':[...]] | ['errors']
     */
    public function actionEditTrackedCity()
    {
        try {
            $city_form = new CityForm();
            $city_form->attributes = [
                'city' => $this->city,
                'binded_airport' => $this->binded_airport,
            ];

            $result = $city_form->editTrackedCity();
        } catch (\Throwable $th) {
            Yii::error($th);
            Yii::$app->response->statusCode = 500;
            return $this->asJson(['errors' => 'Произошла неизвестная ошибка']);
        }

        if ($city_form->hasErrors()) {
            Yii::$app->response->statusCode = (false === $result) ? 500 : 400;
            return $this->asJson(['errors' => $city_form->errors]);
        }

        Yii::$app->response->statusCode = 200;
        return $this->asJson($result);
    }

    /**
     * Прикрепление альтернативного названия
     *
     * @return json ['alt_name': [...]] | ['errors']
     */
    public function actionAttachAltName()
    {
        $new_alt_name = new AltCitysNames();
        $new_alt_name->city = $this->curr_city_id;
        $new_alt_name->alt_name = $this->alt_name;

        if (!$new_alt_name->save()) {
            Yii::$app->response->statusCode = 400;
            return $this->asJson(['errors' => $new_alt_name->errors]);
        }

        if (0 === NewCitys::deleteAll(['name' => $this->alt_name])) {
            Yii::$app->response->statusCode = 500;
            return $this->asJson(['errors' => 'Ошибка при удалении города из списка новых']);
        }

        Yii::$app->response->statusCode = 200;
        return $this->asJson([
            'alt_name' => $new_alt_name->attributes,
        ]);
    }
}
