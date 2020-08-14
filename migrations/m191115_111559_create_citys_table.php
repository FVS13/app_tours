<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%citys}}`.
 */
class m191115_111559_create_citys_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%citys}}', [
            'id' => $this->primaryKey()->notNull(),
            'name' => $this->char(30)->notNull()->unique(),
            'toponymic_analogue' => $this->string(40),
            'city_code' => $this->char(11),//->unique(),
            'time_zone_gmt' => $this->char(6),
            'tracked' => $this->boolean()->defaultValue(false),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%citys}}');
    }
}
