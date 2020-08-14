<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%tariffs_names}}`.
 */
class m191128_173410_create_tariffs_names_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%tariffs_names}}', [
            'id' => $this->primaryKey(),
            'name' => $this->text(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%tariffs_names}}');
    }
}
