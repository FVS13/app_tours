<?php

namespace app\models;

use app\traits\CachingActiveRecords;
use yii\db\ActiveRecord;

/**
 * @property string $name Название тарифа
 */
class TariffsNames extends ActiveRecord
{
    use CachingActiveRecords {
        CachingActiveRecords::initCache as CARInitCache;
    }

    public const DEFAULT_NAME = 'Тариф';
    public const DEFAULT_ID = -1;

    public static function tableName()
    {
        return '{{%tariffs_names}}';
    }

    public static function initCache()
    {
        static::CARInitCache(['name' => 'id']);

        $default_tariff = new static();
        $default_tariff->name = static::DEFAULT_NAME;
        $default_tariff->id = static::DEFAULT_ID;

        static::setCachedValue($default_tariff, 'name', 'id');
        static::setCachedObject($default_tariff);
    }

    /**
     * Возвращает ID в базе данных
     *
     * @param string $name
     * @return integer|null
     */
    public static function findOrCreateId(string $name = null): ?int
    {
        if (empty($name)) {
            return static::DEFAULT_ID;
        }

        if (!static::existsCachedValue('name', $name)) {
            $current_tariff_name = new static();
            $current_tariff_name->name = $name;
            $current_tariff_name->save();

            static::setCachedValue($current_tariff_name, 'name', 'id');
        }

        return static::getCachedValue('name', $name);
    }

    /**
     * @param integer $id
     * @return TariffsNames|null
     */
    public static function getById(int $id): ?TariffsNames
    {
        return static::getCachedObject($id);
    }
}
