<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%airports}}`.
 */
class m191125_142623_create_airports_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%airports}}', [
            'id' => $this->primaryKey(11),
            'location' => $this->integer(11)->unique(),
            'airport_code' => $this->string(4)->unique(),
        ]);

        $this->addForeignKey(
            'FK_location',
            '{{%airports}}',
            'location',
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
        $this->dropForeignKey('FK_location', '{{%airports}}');
        $this->dropTable('{{%airports}}');
    }
}
