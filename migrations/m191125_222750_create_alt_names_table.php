<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%alt_names}}`.
 */
class m191125_222750_create_alt_names_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%alt_names}}', [
            'id' => $this->primaryKey(11),
            'city' => $this->integer(11),
            'alt_name' => $this->string(30)->unique(),
        ]);

        $this->addForeignKey(
            'FK_alt_names',
            '{{%alt_names}}',
            'city',
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
        $this->dropForeignKey('FK_alt_names', '{{%alt_names}}');
        $this->dropTable('{{%alt_names}}');
    }
}
