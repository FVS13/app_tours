<?php

use yii\db\Migration;

/**
 * Class m200721_053416_change_to_null_target_and_progress
 */
class m200721_053416_change_to_null_target_and_progress extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $query  = <<<QUERY
        ALTER TABLE `tours_reports`
            CHANGE `target` `target` VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '0',
            CHANGE `progress` `progress` VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '0';
        QUERY;

        \Yii::$app->db->createCommand($query)->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $query  = <<<QUERY
        ALTER TABLE `tours_reports`
            CHANGE `target` `target` VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
            CHANGE `progress` `progress` VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0';
        QUERY;

        \Yii::$app->db->createCommand($query)->execute();
    }
}
