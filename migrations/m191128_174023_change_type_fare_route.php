<?php

use yii\db\Migration;

/**
 * Class m191128_174023_change_type_fare_route
 */
class m191128_174023_change_type_fare_route extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->delete('{{%flights_tariffs_info}}');
        $this->alterColumn(
            '{{%flights_tariffs_info}}',
            'fare_route',
            $this->integer(11)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m191128_174023_change_type_fare_route cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m191128_174023_change_type_fare_route cannot be reverted.\n";

        return false;
    }
    */
}
