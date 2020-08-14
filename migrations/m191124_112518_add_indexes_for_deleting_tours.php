<?php

use yii\db\Migration;

/**
 * Class m191124_112518_add_index_for_deleting_tours
 */
class m191124_112518_add_indexes_for_deleting_tours extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createIndex(
            'for_deleting',
            '{{%flights%}}',
            ['parse_number', 'validity', 'route_code', 'service_class', 'date']
        );

        $this->createIndex(
            'for_deleting',
            '{{%trips%}}',
            ['parse_number', 'validity', 'route_code', 'departure_time']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('for_deleting', '{{%flights%}}');
        $this->dropIndex('for_deleting', '{{%trips%}}');
    }
}
