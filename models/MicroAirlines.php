<?php

namespace app\models;

use yii\db\ActiveRecord;

class MicroAirlines extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%micro_airlines}}';
    }
}
