<?php

namespace app\models;

use Yii;
use yii\base\Model;

class CityForm extends Model
{
    public $city;
    public $airport;
    public $alt_names;
    public $binded_airport;
    public $city_id;

    public function scenarios()
    {
        return [
            'default' => ['city', 'airport', 'alt_names', 'binded_airport', 'city_id'],
        ];
    }

    /**
     * @return array
     */
    public function addNewTransitCity(): array
    {
        $is_new_city = true;
        $city = Citys::findOne(['name' => $this->city['name']]);

        if (!empty($city)) {
            $is_new_city = false;
        } else {
            $city = new Citys();
        }

        $city->scenario = 'add_new_airport';
        $city->attributes = $this->city;

        if (!$city->validate()) {
            $this->addErrors($city->errors);
            return [];
        }

        $city->save();
        $airport = new Airports();
        $airport->attributes = $this->airport;
        $airport->location = $city->id;

        $alt_names_errors = AltCitysNames::batchValidate($this->alt_names);

        if (!$airport->validate() || !empty($alt_names_errors)) {
            if ($is_new_city) {
                $city->delete();
            }

            $this->addErrors($airport->errors);
            $this->addErrors($alt_names_errors);
            return [];
        }

        $airport->save();
        $city->updateAltNames($this->alt_names);
        NewCitys::deleteAll(['name' => array_merge([$city->name], $this->alt_names)]);

        return [
            'city' => $city->attributes,
            'airport' => $city->airport->attributes,
            'alt_names' => (array) $city->altNames
        ];
    }


    /**
     * @return array|false
     */
    public function editTransitCity()
    {
        $city = Citys::findOne($this->city['id']);

        if (empty($city)) {
            $this->addError('city', 'Редактируемый город не найден');
            return [];
        }

        $city->attributes = $this->city;
        $city->airport->attributes = $this->airport;
        $alt_names_errors = AltCitysNames::batchValidate($this->alt_names, $city);

        if (!$city->validate() || !$city->airport->validate() || !empty($alt_names_errors)) {
            $this->addErrors($city->errors);
            $this->addErrors($city->airport->errors);
            $this->addErrors($alt_names_errors);
            return [];
        }

        if (!$city->save() || !$city->airport->save()) {
            $this->addError('', 'Возникли ошибки при сохранении записи');
            return false;
        }

        $city->updateAltNames($this->alt_names);
        NewCitys::deleteAll(['name' => array_merge([$city->name], $this->alt_names)]);

        $city = Citys::findOne($city->id); // Дабы избавится от кеширования altNames

        return [
            'city' => $city->attributes,
            'airport' => $city->airport->attributes,
            'alt_names' => (array) $city->altNames,
        ];
    }

    /**
     * @return array|false
     */
    public function editTrackedCity()
    {
        $city = Citys::findOne($this->city['id']);
        $airport = Airports::findOne($this->binded_airport['binded_airport_id']);

        if (empty($airport)) {
            $this->addError('', 'Выбранный аэропорт не существует');
            return [];
        }

        if (empty($city)) {
            $this->addError('', 'Редактируемый город не найден');
            return [];
        }

        $city->attributes = $this->city;
        $city->bindedAirport->attributes = $this->binded_airport;
        $city->bindedAirport->is_tracked = isset($this->binded_airport['is_tracked']);

        if (!$city->validate() || !$city->bindedAirport->validate()) {
            $this->addErrors($city->errors);
            $this->addErrors($city->bindedAirport->errors);
            return [];
        }

        if (!$city->save() || !$city->bindedAirport->save()) {
            $this->addError('', 'Возникли ошибки при сохранении записи');
            return false;
        }

        return [
            'city' => $city->attributes,
            'airport' => array_merge(
                $city->airport->attributes ?? [],
                ['location-city' => $city->airport->locationCity],
            ),
            'binded_airport' => array_merge(
                $city->bindedAirport->attributes,
                ['location-city' => $city->bindedAirport->airport->locationCity],
                ['airport-code' => $city->bindedAirport->airport->airport_code],
            ),
        ];
    }

    public function removeTransitCity(): array
    {
        $city = Citys::findOne($this->city_id);

        if (empty($city)) {
            $this->addError('', "Не найден город для удаления (id: $this->city_id)");
            return [];
        }

        if (empty($city->airport)) {
            $error_text = "Не найден аэропорт у транзитного города (id: $this->city_id)";
            $this->addError('', $error_text);
            Yii::warning($error_text);
            return [];
        }

        $exists_binded_city = BindedAirports::find()
            ->where(['binded_airport_id' => $city->airport->id])
            ->exists();

        if ($exists_binded_city) {
            $this->addError('', 'Этот аэропорт прикреплён к отслеживаему городу');
            return [];
        }

        // Если город отслеживается, но не по своему аэропорту
        // Например, аэропорта в нём больше нет
        // То удалится только аэропорт, но останется сам город
        if ($city->tracked === 0) {
            $city->delete();
        } elseif (!empty($city->airport)) {
            $city->airport->delete();
        }

        return [
            'city' => [
                'id' => $city->id,
            ],
            'airport' => [
                'id' => $city->airport->id,
            ],
        ];
    }
}
