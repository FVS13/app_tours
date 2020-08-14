<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%micro_airlines}}`.
 */
class m200218_094731_create_micro_airlines_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%micro_airlines}}', [
            'id' => $this->primaryKey(),
            'airline_name' => $this->string()->notNull(),
            'airline_code' => $this->string()->notNull(),
            'airplane_icao' => $this->string(4)->notNull(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%micro_airlines}}');
    }
}
