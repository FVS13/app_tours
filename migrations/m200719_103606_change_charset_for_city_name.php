<?php

use yii\db\Migration;

/**
 * Class m200719_103606_change_charset_for_city_name
 *
 * Изменения кодировки столбцов с названиями городов,
 * для правильного сравнения. Не учитывалась разница букв "е" и "ё"
 */
class m200719_103606_change_charset_for_city_name extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $query = <<<QUERY
            ALTER TABLE `citys` CHANGE `name` `name` CHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL;
            ALTER TABLE `alt_names` CHANGE `alt_name` `alt_name` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL;
            ALTER TABLE `new_citys` CHANGE `name` `name` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL;
        QUERY;

        \Yii::$app->db->createCommand($query)->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $query = <<<QUERY
            ALTER TABLE `citys` CHANGE `name` `name` CHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
            ALTER TABLE `alt_names` CHANGE `alt_name` `alt_name` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL;
            ALTER TABLE `new_citys` CHANGE `name` `name` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL;
        QUERY;

        \Yii::$app->db->createCommand($query)->execute();
    }
}
