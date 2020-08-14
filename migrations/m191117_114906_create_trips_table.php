<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%trips}}`.
 */
class m191117_114906_create_trips_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%trips}}', [
            'id' => $this->primaryKey(),
            'parse_number' => $this->integer(8)->notNull(),
            'validity' => $this->string(30)->notNull(),
            'collected_at_gmt' => $this->dateTime()->notNull(),
            'route_code' => $this->char(23)->notNull(),
            'seats_count' => $this->char(3)->notNull(),
            'price' => $this->integer(11)->notNull(),
            'bus_num' => $this->text(),
            'marketing_carrier' => $this->text()->notNull(),
            'departure_city_start' => $this->text()->notNull(),
            'arrival_city_end' => $this->text()->notNull(),
            'departure_city' => $this->integer(11)->notNull(),
            'arrival_city' => $this->integer(11)->notNull(),
            'departure_time' => $this->datetime()->notNull(),
            'departure_time_msk' => $this->datetime()->notNull(),
            'arrival_time' => $this->datetime()->notNull(),
            'arrival_time_msk' => $this->datetime()->notNull(),
            'is_deleted' => $this->boolean()->notNull()->defaultValue(false),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%trips}}');
    }
}
