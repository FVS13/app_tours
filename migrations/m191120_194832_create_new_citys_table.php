<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%new_citys}}`.
 */
class m191120_194832_create_new_citys_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%new_citys}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(100)->unique(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%new_citys}}');
    }
}
