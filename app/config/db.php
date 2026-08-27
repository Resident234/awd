<?php

return [
    'class' => \yii\db\Connection::class,
    'dsn' => getenv('DB_DSN') ?: 'pgsql:host=postgres;port=5432;dbname=yii_dev',
    'username' => getenv('DB_USERNAME') ?: 'yii',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset' => 'utf8',

    // Schema cache options (for production environment)
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];
