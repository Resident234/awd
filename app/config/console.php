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
        'forum-post-parser' => [
            'class' => \app\commands\ForumPostParserController::class,
        ],
        'gallery-parser' => [
            'class' => \app\commands\GalleryParserController::class,
        ],
        'member-parser' => [
            'class' => \app\commands\MemberParserController::class,
        ],
        'telegram' => [
            'class' => \app\commands\TelegramController::class,
        ],
    ],
    'container' => [
        'definitions' => [
            \Psr\Log\LoggerInterface::class => static fn (): \app\shared\Forum\Infrastructure\YiiPsrLoggerAdapter =>
                new \app\shared\Forum\Infrastructure\YiiPsrLoggerAdapter(\Yii::$app->getLog()->getLogger()),
            \app\shared\Telegram\Contract\TelegramChannelClientInterface::class => static function (): ?\app\shared\Telegram\Infrastructure\NutgramChannelClient {
                $token = (string)(getenv('TELEGRAM_BOT_TOKEN') ?: '');
                return $token === '' ? null : new \app\shared\Telegram\Infrastructure\NutgramChannelClient($token);
            },
            \app\shared\Telegram\Service\ChannelService::class => static fn (): \app\shared\Telegram\Service\ChannelService =>
                new \app\shared\Telegram\Service\ChannelService(
                    \Yii::createObject(\app\shared\Telegram\Contract\TelegramChannelClientInterface::class),
                    (string)(getenv('TELEGRAM_CHANNEL_ID') ?: '@gsu_travels'),
                    new \app\shared\Telegram\Infrastructure\PublishedDescriptionRepository(\Yii::$app->getDb()),
                ),
            \app\shared\Publications\Service\PublicationsService::class => static fn (): \app\shared\Publications\Service\PublicationsService =>
                new \app\shared\Publications\Service\PublicationsService(
                    new \app\shared\Publications\Infrastructure\PublicationRepository(\Yii::$app->getDb()),
                    new \app\shared\Forum\Infrastructure\YiiPsrLoggerAdapter(\Yii::$app->getLog()->getLogger()),
                    \Yii::createObject(\app\shared\Telegram\Service\ChannelService::class),
                ),
            \app\shared\Forum\Contract\ForumHttpClientInterface::class => static fn (): \app\shared\Forum\Infrastructure\ForumHttpClient =>
                new \app\shared\Forum\Infrastructure\ForumHttpClient(
                    30,
                    3,
                    500000,
                    'https://forum.awd.ru/ucp.php?mode=login',
                    (string)(getenv('FORUM_LOGIN_USERNAME') ?: ''),
                    (string)(getenv('FORUM_LOGIN_PASSWORD') ?: ''),
                ),
            \app\shared\Forum\Service\ForumScanService::class => static fn (): \app\shared\Forum\Service\ForumScanService =>
                new \app\shared\Forum\Service\ForumScanService(
                    new \app\shared\Forum\Infrastructure\ForumRepository(\Yii::$app->getDb()),
                    \Yii::createObject(\app\shared\Forum\Contract\ForumHttpClientInterface::class),
                    new \app\shared\Forum\Service\ForumHtmlParser(),
                    new \app\shared\Forum\Infrastructure\YiiPsrLoggerAdapter(\Yii::$app->getLog()->getLogger()),
                ),
            \app\shared\Forum\Service\ForumPostScanService::class => static fn (): \app\shared\Forum\Service\ForumPostScanService =>
                new \app\shared\Forum\Service\ForumPostScanService(
                    new \app\shared\Forum\Infrastructure\ForumRepository(\Yii::$app->getDb()),
                    \Yii::createObject(\app\shared\Forum\Contract\ForumHttpClientInterface::class),
                    new \app\shared\Forum\Service\ForumPostPageParser(),
                    new \app\shared\Forum\Infrastructure\YiiPsrLoggerAdapter(\Yii::$app->getLog()->getLogger()),
                ),
            \app\shared\Gallery\Service\GalleryScanService::class => static fn (): \app\shared\Gallery\Service\GalleryScanService =>
                new \app\shared\Gallery\Service\GalleryScanService(
                    new \app\shared\Gallery\Infrastructure\GalleryRepository(\Yii::$app->getDb()),
                    \Yii::createObject(\app\shared\Forum\Contract\ForumHttpClientInterface::class),
                    new \app\shared\Gallery\Service\GalleryAlbumPageParser(),
                    new \app\shared\Forum\Infrastructure\YiiPsrLoggerAdapter(\Yii::$app->getLog()->getLogger()),
                ),
            \app\shared\Forum\Service\MemberScanService::class => static fn (): \app\shared\Forum\Service\MemberScanService =>
                new \app\shared\Forum\Service\MemberScanService(
                    new \app\shared\Forum\Infrastructure\ForumRepository(\Yii::$app->getDb()),
                    \Yii::createObject(\app\shared\Forum\Contract\ForumHttpClientInterface::class),
                    new \app\shared\Forum\Service\MemberProfilePageParser(),
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
    // The debug module is intentionally NOT enabled for the console app:
    // long-running parser commands produce huge debug logs and exhaust memory.
}

return $config;
