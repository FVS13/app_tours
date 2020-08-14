<?php

use yii\db\Migration;

/**
 * Class m191227_002014_add_indexes_for_view_data
 */
class m191227_002014_add_indexes_for_view_data extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createIndex(
            'for_view_data',
            '{{%flights%}}',
            ['validity', 'service_class', 'route_code', 'is_deleted', 'collected_at_gmt']
        );

        $this->createIndex(
            'for_view_data',
            '{{%trips%}}',
            ['validity', 'route_code', 'is_deleted', 'collected_at_gmt']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('for_view_data', '{{%flights%}}');
        $this->dropIndex('for_view_data', '{{%trips%}}');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191227_002014_add_indexes_for_view_data cannot be reverted.\n";

        return false;
    }
    */
}
