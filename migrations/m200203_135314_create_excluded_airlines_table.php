<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%excluded_airlines}}`.
 */
class m200203_135314_create_excluded_airlines_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%excluded_airlines}}', [
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
        $this->dropTable('{{%excluded_airlines}}');
    }
}
