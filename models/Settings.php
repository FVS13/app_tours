<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property integer $state Состояние настройки (вкл = 1/выкл = 0)
 */
class Settings extends ActiveRecord
{
    public static function getState(string $setting_name): ?int
    {
        $setting = Settings::findOne(['name' => $setting_name]);

        return $setting->state ?? null;
    }

    public static function setState(string $setting_name, bool $state): bool
    {
        $setting = Settings::findOne(['name' => $setting_name]);

        if (empty($setting)) {
            return false;
        }

        $setting->state = (int) $state;

        return $setting->save();
    }
}
