<?php

use yii\db\Migration;

/**
 * Class m200714_065113_add_index_for_export
 */
class m200714_065113_add_index_for_export extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createIndex(
            'for_export',
            '{{%flights%}}',
            ['is_deleted', 'route_code', 'service_class', 'date']
        );

        $this->createIndex(
            'for_export',
            '{{%trips%}}',
            ['is_deleted', 'route_code', 'departure_time']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('for_export', '{{%flights%}}');
        $this->dropIndex('for_export', '{{%trips%}}');
    }
}
