<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%icao_codes}}`.
 */
class m200212_102409_create_icao_codes_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%icao_codes}}', [
            'id' => $this->primaryKey(),
            'airline_code' => $this->string(127)->notNull(),
            'flight_num' => $this->string(20)->notNull(),
            'date' => $this->dateTime()->notNull(),
            'icao' => $this->char(4),
            'updated_at' => $this->dateTime(),
        ]);

        $this->createIndex(
            'codes',
            'icao_codes',
            ['airline_code', 'flight_num', 'date'],
            true
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('codes', 'icao_codes');
        $this->dropTable('{{%icao_codes}}');
    }
}
