<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%routes}}`.
 */
class m191125_142051_create_routes_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%routes}}', [
            'id' => $this->primaryKey(),
            'departure_city' => $this->integer(11)->notNull(),
            'arrival_city' => $this->integer(11)->notNull(),
            'distance_by_air' => $this->integer(11),
            'distance_by_roads' => $this->integer(11),
        ]);

        $this->addForeignKey(
            'FK_departure_city',
            '{{%routes}}',
            'departure_city',
            '{{%citys}}',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'FK_arrival_city',
            '{{%routes}}',
            'arrival_city',
            '{{%citys}}',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('FK_arrival_city', '{{%routes}}');
        $this->dropForeignKey('FK_departure_city', '{{%routes}}');
        $this->dropTable('{{%routes}}');
    }
}
