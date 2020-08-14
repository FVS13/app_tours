<?php

use yii\db\Migration;

/**
 * Class m191204_220015_add_indexes_for_statistics
 */
class m191204_220015_add_indexes_for_statistics extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createIndex(
            'for_statistics',
            '{{%flights%}}',
            ['route_code', 'service_class', 'validity', 'date', 'is_deleted', 'collected_at_gmt']
        );

        $this->createIndex(
            'for_statistics',
            '{{%trips%}}',
            ['route_code', 'validity', 'departure_time', 'is_deleted', 'collected_at_gmt']
        );

        $this->createIndex(
            'for_statistics',
            '{{%invalid_tours%}}',
            ['route_code', 'tour_type', 'validity', 'errors_count']
        );

        $this->createIndex(
            'validity',
            '{{%flights%}}',
            ['validity']
        );

        $this->createIndex(
            'validity',
            '{{%trips%}}',
            ['validity']
        );

        $this->createIndex(
            'date',
            '{{%flights%}}',
            ['date']
        );

        $this->createIndex(
            'departure_time',
            '{{%trips%}}',
            ['departure_time']
        );

        $this->createIndex(
            'for_statistics',
            '{{%flights_tariffs_info%}}',
            ['price']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('for_statistics', '{{%flights%}}');
        $this->dropIndex('validity', '{{%flights%}}');
        $this->dropIndex('date', '{{%flights%}}');

        $this->dropIndex('for_statistics', '{{%trips%}}');
        $this->dropIndex('validity', '{{%trips%}}');
        $this->dropIndex('departure_time', '{{%trips%}}');

        $this->dropIndex('for_statistics', '{{%invalid_tours%}}');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191204_220015_add_indexes_for_statistics cannot be reverted.\n";

        return false;
    }
    */
}
