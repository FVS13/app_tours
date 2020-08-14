<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%airlines}}`.
 */
class m200207_172915_add_alt_code_columns_to_airlines_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
         $this->addColumn('airlines', 'alt_code1', $this->char(255));
         $this->addColumn('airlines', 'alt_code2', $this->char(255));
         $this->addColumn('airlines', 'alt_code3', $this->char(255));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('airlines', 'alt_code1');
        $this->dropColumn('airlines', 'alt_code2');
        $this->dropColumn('airlines', 'alt_code3');
    }
}
