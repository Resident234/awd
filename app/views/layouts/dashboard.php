<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $content */

use app\assets\DashboardAsset;
use app\widgets\Alert;
use yii\helpers\Html;
use yii\helpers\Url;

DashboardAsset::register($this);

$uiKitUrl = Yii::getAlias('@web/ui-kit');

$this->registerCsrfMetaTags();
$this->registerMetaTag(
    ['charset' => Yii::$app->charset],
    'charset',
);
$this->registerMetaTag(
    [
        'name' => 'viewport',
        'content' => 'width=device-width, initial-scale=1',
    ],
);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <?php $this->head() ?>
    <title><?= Html::encode($this->title) ?></title>
</head>
<body>
<?php $this->beginBody() ?>

<div class="page-wrapper">

    <div class="main-container">

        <nav id="sidebar" class="sidebar-wrapper">

            <div class="app-brand">
                <a href="<?= Yii::$app->homeUrl ?>">
                    <img src="<?= $uiKitUrl ?>/images/logo.svg" class="logo" alt="AWD TRVL">
                </a>
            </div>

            <div class="sidebarMenuScroll">
                <ul class="sidebar-menu">
                    <li class="active current-page">
                        <a href="<?= Yii::$app->homeUrl ?>">
                            <i class="bi bi-speedometer2"></i>
                            <span class="menu-text">Дашборд</span>
                        </a>
                    </li>
                    <li>
                        <a href="#!">
                            <i class="bi bi-telegram"></i>
                            <span class="menu-text">Канал TRVL</span>
                        </a>
                    </li>
                    <li>
                        <a href="#!">
                            <i class="bi bi-collection"></i>
                            <span class="menu-text">Очередь публикаций</span>
                        </a>
                    </li>
                    <li>
                        <a href="#!">
                            <i class="bi bi-input-cursor-text"></i>
                            <span class="menu-text">Стили текста</span>
                        </a>
                    </li>
                    <li>
                        <a href="#!">
                            <i class="bi bi-images"></i>
                            <span class="menu-text">Медиа</span>
                        </a>
                    </li>
                    <li>
                        <a href="#!">
                            <i class="bi bi-cloud-download"></i>
                            <span class="menu-text">Парсер источников</span>
                        </a>
                    </li>
                    <li>
                        <a href="#!">
                            <i class="bi bi-robot"></i>
                            <span class="menu-text">Telegram-бот</span>
                        </a>
                    </li>
                </ul>
            </div>

        </nav>

        <div class="app-container">

            <div class="app-header d-flex align-items-center">

                <div class="d-flex">
                    <button type="button" class="btn bg-primary me-2 toggle-sidebar" id="toggle-sidebar">
                        <i class="bi bi-layout-sidebar fs-5 text-white"></i>
                    </button>
                    <button type="button" class="btn bg-primary-subtle me-2 pin-sidebar" id="pin-sidebar">
                        <i class="bi bi-layout-sidebar fs-5 text-primary"></i>
                    </button>
                </div>

                <div class="app-brand-sm d-lg-none d-md-block">
                    <a href="<?= Yii::$app->homeUrl ?>">
                        <img src="<?= $uiKitUrl ?>/images/logo-sm.svg" class="logo" alt="AWD TRVL">
                    </a>
                </div>

                <div class="header-actions">
                    <div class="search-container d-lg-block d-none me-3">
                        <input type="text" class="form-control" id="searchAny" placeholder="Поиск">
                        <i class="bi bi-search"></i>
                    </div>

                    <div class="dropdown ms-3">
                        <a id="userSettings" class="dropdown-toggle d-flex align-items-center py-1 avatar-box" href="#!"
                           role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle fs-3 lh-1 text-secondary"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end shadow-lg p-3">
                            <div class="user-header d-flex align-items-center mb-3">
                                <i class="bi bi-person-circle fs-2 me-2 text-primary"></i>
                                <div>
                                    <h6 class="mb-0">Администратор</h6>
                                    <small class="text-muted">AWD TRVL</small>
                                </div>
                            </div>
                            <?php if (Yii::$app->user->isGuest): ?>
                                <a class="dropdown-item d-flex align-items-center py-2 border mb-1" href="<?= Url::to(['/site/login']) ?>">
                                    <i class="bi bi-box-arrow-in-right me-2 text-primary"></i>
                                    <span>Войти</span>
                                </a>
                            <?php else: ?>
                                <a class="dropdown-item d-flex align-items-center py-2 border mb-1" href="#!">
                                    <i class="bi bi-person me-2 text-primary"></i>
                                    <span>Профиль</span>
                                </a>
                                <?= Html::a(
                                    '<i class="bi bi-box-arrow-right me-2 text-primary"></i>Выйти',
                                    ['/site/logout'],
                                    [
                                        'class' => 'dropdown-item d-flex align-items-center py-2 border',
                                        'data-method' => 'post',
                                    ],
                                ) ?>
                            <?php endif ?>
                        </div>
                    </div>
                </div>

            </div>

            <div class="app-hero-header d-flex align-items-center">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <i class="bi bi-house"></i>
                        <a href="<?= Yii::$app->homeUrl ?>">Home</a>
                    </li>
                    <li class="breadcrumb-item" aria-current="page"><?= Html::encode($this->title) ?></li>
                </ol>
            </div>

            <div class="app-body">
                <?= Alert::widget() ?>
                <?= $content ?>
            </div>

            <div class="app-footer">
                <span>© AWD <?= date('Y') ?></span>
            </div>

        </div>

    </div>

</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
