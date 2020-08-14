<?php

use yii\db\Migration;

/**
 * Class m191204_031121_add_default_city
 */
class m191204_031121_add_default_city extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->insert('{{%citys}}', [
            'id' => -1,
            'name' => 'Без названия',
            'city_code' => 'нет_кода',
            'tracked' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('{{%citys}}', ['id' => -1]);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191204_031121_add_default_city cannot be reverted.\n";

        return false;
    }
    */
}
