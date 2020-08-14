<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%airlines}}`.
 */
class m191115_113452_create_airlines_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%airlines}}', [
            'id' => $this->primaryKey(),
            'name' => $this->char(255),
            'code' => $this->char(255)->notNull(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%airlines}}');
    }
}
