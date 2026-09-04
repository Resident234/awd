<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'app\commands',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@tests' => '@app/tests',
    ],
    'components' => [
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
        'log' => [
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
    ],
    'controllerMap' => [
        'forum-parser' => [
            'class' => \app\commands\ForumParserController::class,
        ],
    ],
    'container' => [
        'definitions' => [
            \Psr\Log\LoggerInterface::class => static fn (): \app\shared\Forum\Infrastructure\YiiPsrLoggerAdapter =>
                new \app\shared\Forum\Infrastructure\YiiPsrLoggerAdapter(\Yii::$app->getLog()->getLogger()),
            \app\shared\Forum\Contract\ForumHttpClientInterface::class => \app\shared\Forum\Infrastructure\ForumHttpClient::class,
            \app\shared\Forum\Service\ForumScanService::class => static fn (): \app\shared\Forum\Service\ForumScanService =>
                new \app\shared\Forum\Service\ForumScanService(
                    new \app\shared\Forum\Infrastructure\ForumRepository(\Yii::$app->getDb()),
                    \Yii::createObject(\app\shared\Forum\Contract\ForumHttpClientInterface::class),
                    new \app\shared\Forum\Service\ForumHtmlParser(),
                    new \app\shared\Forum\Infrastructure\YiiPsrLoggerAdapter(\Yii::$app->getLog()->getLogger()),
                ),
        ],
    ],
    'params' => $params,
    /*
    'controllerMap' => [
        'fixture' => [ // Fixture generation command line.
            'class' => 'yii\faker\FixtureController',
        ],
    ],
    */
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => \yii\gii\Module::class,
    ];
    // configuration adjustments for 'dev' environment
    // requires version `2.1.21` of yii2-debug module
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => \yii\debug\Module::class,
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
