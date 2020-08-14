<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%binded_airports}}`.
 */
class m191207_014827_create_binded_airports_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%binded_airports}}', [
            'id' => $this->primaryKey(11),
            'tracked_city_id' => $this->integer(11)->unique(),
            'binded_airport_id' => $this->integer(11),
            'distance_to_airport' => $this->integer(6),
            'is_tracked' => $this->boolean()->defaultValue(false),
        ]);

        $this->addForeignKey(
            'FK_binded_airport_id',
            'binded_airports',
            'binded_airport_id',
            'airports',
            'id'
        );

        $this->addForeignKey(
            'FK_tracked_city_id',
            'binded_airports',
            'tracked_city_id',
            'citys',
            'id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('FK_tracked_city_id', '{{%binded_airports}}');
        $this->dropForeignKey('FK_binded_airport_id', '{{%binded_airports}}');
        $this->dropTable('{{%binded_airports}}');
    }
}
