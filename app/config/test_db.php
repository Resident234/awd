<?php

$db = require __DIR__ . '/db.php';
// test database! Important not to run tests on production or development databases
// DB_DSN/DB_USERNAME/DB_PASSWORD env variables override the default,
// so tests can run against the docker-compose PostgreSQL instance.
$db['dsn'] = getenv('DB_DSN') ?: 'pgsql:host=postgres;port=5432;dbname=yii_test';
$db['username'] = getenv('DB_USERNAME') ?: 'yii';
$db['password'] = getenv('DB_PASSWORD') ?: '';

return $db;
