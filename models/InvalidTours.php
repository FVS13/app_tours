<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

class InvalidTours extends ActiveRecord
{
    public static function tablename()
    {
        return '{{%invalid_tours}}';
    }

    public function scenarios()
    {
        return [
            'default' => ['validity', 'route_code', 'tour_type', 'date'],
        ];
    }

    public function rules()
    {
        return [
            ['validity', 'required', 'message' => 'Не указан источник'],
            ['route_code', 'required', 'message' => 'Не указан код направления'],
            ['tour_type', 'required', 'message' => 'Не указан тип рейса'],
            ['date', 'required', 'message' => 'Не указана дата'],

            ['validity',
                'string',
                'min' => 1,
                'max' => 30,
                'tooShort' => 'Источник не может быть пустой строкой',
                'tooLong' => 'Источник не должен быть длиннее 30 символов',
            ],
            ['route_code',
                'string',
                'min' => 7,
                'max' => 23,
                'tooShort' => 'Код направления не может быть короче 7 символов',
                'tooLong' => 'Код направления не может быть длиннее 23 символов',
            ],
            ['tour_type',
                'string',
                'min' => 1,
                'max' => 3,
                'tooShort' => 'Тип рейса не может быть пустой строкой',
                'tooLong' => 'Тип рейса не должен быть длиннее 3 символов',
            ],
            ['date',
                'string',
                'length' => 10,
                'notEqual' => 'Длина даты должна быть 10 символов',
            ],

            [['date'], 'date', 'format' => 'php:Y-m-d', 'message' => 'Неверный формат даты'],
        ];
    }

    public function afterValidate()
    {
        if ($this->hasErrors()) {
            Yii::error('Ошибка сохранения ошибки валидации данных о рейсе');
            Yii::error($this->errors);
            Yii::error($this->attributes);
        }
    }

    public static function addTourFileError(
        int $parse_number,
        array $path_info,
        int $errors_count,
        string $error_type = 'validation'
    ) {
        $invalid_tours = new static();

        $invalid_tours->attributes = $path_info;
        $invalid_tours->parse_number = $parse_number;
        $invalid_tours->errors_count = $errors_count;
        $invalid_tours->error_type = $error_type;

        $invalid_tours->save();
    }
}
