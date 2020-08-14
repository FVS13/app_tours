<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%flights}}`.
 */
class m191115_114034_create_tables_flights extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%flights}}', [
            'id' => $this->primaryKey(11),
            'parse_number' => $this->integer(8)->notNull(),
            'validity' => $this->string(20)->notNull(),
            'route_code' => $this->char(23)->notNull(),
            'service_class' => $this->char(1)->notNull(),
            'date' => $this->date()->notNull(),
            'collected_at_gmt' => $this->dateTime()->notNull(),
            'is_deleted' => $this->boolean()->notNull()->defaultValue(false),
        ]);

        $this->createTable('{{%flights_segments}}', [
            'id' => $this->primaryKey(11),
            'flight_ref' => $this->integer(11)->notNull(),
            'segment_number' => $this->integer(1)->notNull(),
            'flight_num' => $this->string(20)->notNull(),
            'marketing_carrier' => $this->integer(11)->notNull(),
            'departure_city' => $this->integer(11)->notNull(),
            'arrival_city' => $this->integer(11)->notNull(),
            'departure_time' => $this->datetime()->notNull(),
            'departure_time_msk' => $this->datetime()->notNull(),
            'arrival_time' => $this->datetime()->notNull(),
            'arrival_time_msk' => $this->datetime()->notNull(),
        ]);

        $this->createTable('{{%flights_tariffs_info}}', [
            'id' => $this->primaryKey(11),
            'flight_ref' => $this->integer(11)->notNull(),
            'seats_count' => $this->char(3)->notNull(),
            'price' => $this->integer(9)->notNull(),
            'currency_code' => $this->char(4)->notNull(),
            'fare_route' => $this->string(40)->defaultValue('Тариф'),
        ]);

        $this->addForeignKey(
            'FK_flight_segment_backlink',
            'flights_segments',
            'flight_ref',
            'flights',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'FK_flight_fares_backlink',
            'flights_tariffs_info',
            'flight_ref',
            'flights',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('FK_flight_fares_backlink', 'flights_tariffs_info');
        $this->dropForeignKey('FK_flight_segment_backlink', 'flights_segments');
        $this->dropTable('{{%flights_tariffs_info}}');
        $this->dropTable('{{%flights_segments}}');
        $this->dropTable('{{%flights}}');
    }
}
