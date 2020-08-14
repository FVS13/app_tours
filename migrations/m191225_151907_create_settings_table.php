<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%settings}}`.
 */
class m191225_151907_create_settings_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%settings}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(50)->notNull(),
            'params' => $this->text(),
            'state' => $this->boolean()->defaultValue(false),
        ]);


        $this->batchInsert('{{%settings}}',
        ['name', 'state'],
        [
            ['auto_parse', false],
            ['auto_export', false],
            ['create_xslx', false],
            ['create_archive', false],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%settings}}');
    }
}
