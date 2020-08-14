<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%invalid_tours}}`.
 */
class m191118_130117_create_invalid_tours_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%invalid_tours}}', [
            'id' => $this->primaryKey(),
            'parse_number' => $this->integer(8)->notNull(),
            'validity' => $this->string(30)->notNull(),
            'route_code' => $this->char(23)->notNull(),
            'tour_type' => $this->char(3)->notNull(),
            'date' => $this->date()->notNull(),
            'errors_count' => $this->integer(8)->notNull(),
            'error_type' => $this->string(40)->notNull()->defaultValue('validation'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%invalid_tours}}');
    }
}
