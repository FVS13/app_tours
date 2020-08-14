<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%aircraft_reference}}`.
 */
class m200213_041738_create_aircraft_reference_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%aircraft_reference}}', [
            'id' => $this->primaryKey(),
            'airline_code' => $this->string(127)->notNull(),
            'icao' => $this->char(4),
            'airplane_model' => $this->string(20)->notNull(),
            'passenger_capacity' => $this->integer(6)->notNull(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%aircraft_reference}}');
    }
}
