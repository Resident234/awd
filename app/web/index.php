<?php

declare(strict_types=1);

// Both flags come from the environment (compose reads them from `.env`), so the
// repository carries no debug switch: without the variables the app runs as
// production.
defined('YII_DEBUG') or define('YII_DEBUG', filter_var(getenv('YII_DEBUG'), FILTER_VALIDATE_BOOLEAN));
defined('YII_ENV') or define('YII_ENV', (string)(getenv('YII_ENV') ?: 'prod'));

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';

(new yii\web\Application($config))->run();
