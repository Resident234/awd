<?php

declare(strict_types=1);

namespace app\assets;

use yii\web\AssetBundle;

/**
 * UI-kit based asset bundle for the TRVL dashboard.
 */
class DashboardAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'ui-kit/assets/fonts/bootstrap/bootstrap-icons.css',
        'ui-kit/assets/css/main.min.css',
        'ui-kit/assets/vendor/overlay-scroll/OverlayScrollbars.min.css',
    ];
    public $js = [
        'ui-kit/assets/js/jquery.min.js',
        'ui-kit/assets/js/bootstrap.bundle.min.js',
        'ui-kit/assets/vendor/overlay-scroll/jquery.overlayScrollbars.min.js',
        'ui-kit/assets/vendor/overlay-scroll/custom-scrollbar.js',
        'ui-kit/assets/js/custom.js',
    ];
}
