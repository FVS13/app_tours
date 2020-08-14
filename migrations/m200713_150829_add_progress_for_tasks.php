<?php

use yii\db\Migration;

/**
 * Class m200713_150829_add_progress_for_tasks
 */
class m200713_150829_add_progress_for_tasks extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('tours_reports', 'target', $this->string(40)->defaultValue(0)->notNull());
        $this->addColumn('tours_reports', 'progress', $this->string(40)->defaultValue(0)->notNull());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('tours_reports', 'target');
        $this->dropColumn('tours_reports', 'progress');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200713_150829_add_progress_for_tasks cannot be reverted.\n";

        return false;
    }
    */
}
