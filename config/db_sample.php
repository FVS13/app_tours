<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=%%local_db_name%%',
    'username' => '%%local_user_name%%',
    'password' => '%%local_password%%',
    'charset' => 'utf8',

    // Schema cache options (for production environment)
    // 'enableSchemaCache' => true,
    // 'schemaCacheDuration' => 6000,
    // 'schemaCache' => 'cache',
];
