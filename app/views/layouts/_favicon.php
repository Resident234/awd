<?php

declare(strict_types=1);

/** @var yii\web\View $this */

// The SVG is the icon: one drawing for a 16px tab and a 512px tile. The ICO
// stays behind it for the clients that ask for `/favicon.ico` on their own.
$this->registerLinkTag(
    [
        'rel' => 'icon',
        'type' => 'image/svg+xml',
        'href' => Yii::getAlias('@web/favicon.svg'),
    ],
);
$this->registerLinkTag(
    [
        'rel' => 'alternate icon',
        'type' => 'image/x-icon',
        'href' => Yii::getAlias('@web/favicon.ico'),
    ],
);
