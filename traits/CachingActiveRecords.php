<?php

namespace app\traits;

use Yii;
use yii\base\InvalidArgumentException;
use yii\base\UnknownPropertyException;
use yii\db\ActiveRecord;

/**
 * Простое кеширование связей между атрибутами
 *
 * Запоминаются пары: [ $имя_атрибута_А ][ (string) $объект->$имя_атрибута_А ] = $объект->$имя_атрибута_Б;
 * Значение $объект->$имя_атрибута_А всегда должно быть непустым в строковом представлении,
 * в противном случае будет выброшено исключение.
 *
 * При установке нового значения кеш не перестраивается.
 *
 * Также хранится соответствие [ $объект->первичный_ключ ] = $объект;
 */
trait CachingActiveRecords
{
    /**
     * Тут запоминаются объекты по их первичным ключам
     *
     * @var array<mixed,ActiveRecord> $objects_by_id
     * @static
     */
    private static $objects_by_id = [];

    /**
     * Тут запоминаются значения одних атрибутов, по значению других:
     * ['city_code'][код_города] = 'Имя города'.
     *
     * @var array<string,array<string,mixed>>
     * @static
     */
    private static $values_by_attributes;

    /**
     * @var boolean
     * @static
     */
    private static $is_fulled_cache = false;

    /**
     * Основной метод, заполняет кеш
     *
     * @param array<string,string> $caching_attributes Названия атрибутов: ключевой => сохраняемый (см. описание трейта)
     * @param string[] $with Для with построителя запросов к БД
     * @param boolean $update_cache Обновить содержимое кеша, если он уже был заполнен
     * @throws InvalidArgumentException
     * @return void
     */
    public static function initCache(array $caching_attributes = [], array $with = [], bool $update_cache = false)
    {
        if (static::$is_fulled_cache && !$update_cache) {
            return;
        }

        foreach ($caching_attributes as $key => $value) {
            if (!is_string($value) || !is_string($key)) {
                throw new InvalidArgumentException('Название атрибута должно быть строкой');
            }
        }

        static::$objects_by_id = [];
        static::$values_by_attributes = [];

        $objects = static::find()->with($with)->all();

        foreach ($objects as $object) {
            static::setCachedObject($object);

            /**
             * @var string $key_attr Название атрибута, по которому кешируются значения
             * @var string $caching_attr Название атрибута, значения которого кешируются
             */
            foreach ($caching_attributes as $key_attr => $caching_attr) {
                static::setCachedValue($object, $key_attr, $caching_attr);
            }
        }

        static::$is_fulled_cache = true;
    }

    /**
     * Запоминает значение атрибута $caching_attr, по значению ключевого атрибута $key_attr;
     * значение $key_attr должно быть непустым
     *
     * @param ActiveRecord $object
     * @param string $key_attr Название атрибута, по которому кешируются значения
     * @param string $caching_attr Название атрибута, значения которого кешируются
     * @throws InvalidArgumentException
     * @return void
     */
    public static function setCachedValue(ActiveRecord $object, string $key_attr, string $caching_attr)
    {
        try {
            $key = $object->$key_attr;
            $value = $object->$caching_attr;
        } catch (UnknownPropertyException $th) {
            Yii::error('setCachedValue(): Попытка закешировать несуществующий атрибут');
            Yii::error($th);
            throw $th;
        } catch (\Throwable $th) {
            Yii::error('setCachedValue(): Неизвестная ошибка');
            Yii::error($th);
            throw $th;
        }

        if (empty($key)) {
            throw new InvalidArgumentException('Значение ключевого атрибута не должно быть пустым');
        }

        static::$values_by_attributes[ $key_attr ][ (string) $key ] = $value;
    }

    /**
     * Запоминает объект по первичному ключу
     *
     * @param ActiveRecord $object
     * @return void
     */
    public static function setCachedObject(ActiveRecord $object)
    {
        static::$objects_by_id[ $object->getPrimaryKey() ] = $object;
    }

    /**
     * Возвращает сохранённое значение или null,
     * если по данному ключу ничего не сохранено
     *
     * @param string $attribute_name Имя ключевого атрибута
     * @param string $key Значение ключевого атрибута
     * @return mixed|null
     */
    public static function getCachedValue(string $attribute_name, string $key)
    {
        return static::$values_by_attributes[ $attribute_name ][ (string) $key ] ?? null;
    }

    /**
     * Возвращает закешированный объект по первичному ключу,
     * если объект не найден, возвращает null
     *
     * @param mixed $id
     * @return ActiveRecord|null
     */
    public static function getCachedObject($id): ?ActiveRecord
    {
        return static::$objects_by_id[ $id ] ?? null;
    }

    /**
     * Проверяет, сохранено ли что-то по переданному ключу
     *
     * @param string $attribute_name Имя ключевого атрибута
     * @param string $key Значение ключевого атрибута
     * @return bool Результат проверки
     */
    public static function existsCachedValue(string $attribute_name, string $key): bool
    {
        return array_key_exists($attribute_name, static::$values_by_attributes)
            && array_key_exists($key, static::$values_by_attributes[ $attribute_name ]);
    }
}
