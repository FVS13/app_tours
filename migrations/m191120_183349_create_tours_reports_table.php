<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%tours_reports}}`.
 */
class m191120_183349_create_tours_reports_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%tours_reports}}', [
            'id' => $this->primaryKey(),
            'parse_number' => $this->integer(1),
            'action' => $this->string(13)->notNull(),
            'start_time' => $this->datetime()->notNull(),
            'end_time' => $this->datetime(),
            'status' => $this->string(30),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%tours_reports}}');
    }
}
