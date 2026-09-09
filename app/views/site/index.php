<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var bool $channelConnected */

$this->title = 'Дашборд TRVL';
?>
<!-- Row start -->
<div class="row">
    <div class="col-xl-3 col-sm-6 col-12">
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2">
                    <div class="icon-box md bg-primary-subtle rounded-5">
                        <i class="bi bi-telegram text-primary"></i>
                    </div>
                    <div class="d-flex justify-content-between w-100">
                        <div>
                            <p class="text-muted small mb-1">Опубликовано сегодня</p>
                            <h5 class="m-0 fw-semibold">12</h5>
                        </div>
                        <div class="text-end">
                            <h5 class="mb-1 fw-semibold">+3</h5>
                            <p class="text-success small m-0 fw-semibold">
                                <i class="bi bi-arrow-up-right"></i> 25%
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6 col-12">
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2">
                    <div class="icon-box md bg-success-subtle rounded-5">
                        <i class="bi bi-collection text-success"></i>
                    </div>
                    <div class="d-flex justify-content-between w-100">
                        <div>
                            <p class="text-muted small mb-1">В очереди на публикацию</p>
                            <h5 class="m-0 fw-semibold">48</h5>
                        </div>
                        <div class="text-end">
                            <h5 class="mb-1 fw-semibold">+7</h5>
                            <p class="text-success small m-0 fw-semibold">
                                <i class="bi bi-arrow-up-right"></i> 14%
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6 col-12">
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2">
                    <div class="icon-box md bg-info-subtle rounded-5">
                        <i class="bi bi-clock-history text-info"></i>
                    </div>
                    <div class="d-flex justify-content-between w-100">
                        <div>
                            <p class="text-muted small mb-1">Отложенных постов</p>
                            <h5 class="m-0 fw-semibold">9</h5>
                        </div>
                        <div class="text-end">
                            <h5 class="mb-1 fw-semibold">+2</h5>
                            <p class="text-success small m-0 fw-semibold">
                                <i class="bi bi-arrow-up-right"></i> 22%
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6 col-12">
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2">
                    <div class="icon-box md bg-danger-subtle rounded-5">
                        <i class="bi bi-cloud-download text-danger"></i>
                    </div>
                    <div class="d-flex justify-content-between w-100">
                        <div>
                            <p class="text-muted small mb-1">Спарсено элементов</p>
                            <h5 class="m-0 fw-semibold">1 246</h5>
                        </div>
                        <div class="text-end">
                            <h5 class="mb-1 fw-semibold">+58</h5>
                            <p class="text-success small m-0 fw-semibold">
                                <i class="bi bi-arrow-up-right"></i> 4.6%
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Row end -->

<!-- Row start -->
<div class="row">
    <div class="col-xxl-8 col-sm-12 col-12">

        <!-- Publication queue -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title">Очередь публикаций</h5>
            </div>
            <div class="card-body pt-0">
                <div class="table-outer">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Заголовок</th>
                                <th scope="col">Стиль</th>
                                <th scope="col">Время публикации</th>
                                <th scope="col">Статус</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>1024</td>
                                <td>10 маршрутов по Грузии</td>
                                <td><span class="badge bg-primary-subtle text-primary">TRVL Basic</span></td>
                                <td>Сегодня, 18:00</td>
                                <td><span class="badge bg-warning">Ожидает</span></td>
                            </tr>
                            <tr>
                                <td>1025</td>
                                <td>Как собрать рюкзак в поход</td>
                                <td><span class="badge bg-success-subtle text-success">Adventure</span></td>
                                <td>Сегодня, 21:30</td>
                                <td><span class="badge bg-info">Стилизация</span></td>
                            </tr>
                            <tr>
                                <td>1026</td>
                                <td>Ночной Стамбул: маршрут выходного дня</td>
                                <td><span class="badge bg-info-subtle text-info">City Walk</span></td>
                                <td>Завтра, 09:00</td>
                                <td><span class="badge bg-secondary">Черновик</span></td>
                            </tr>
                            <tr>
                                <td>1027</td>
                                <td>Бюджетные страны Азии</td>
                                <td><span class="badge bg-primary-subtle text-primary">TRVL Basic</span></td>
                                <td>Завтра, 12:00</td>
                                <td><span class="badge bg-success">Готов</span></td>
                            </tr>
                            <tr>
                                <td>1028</td>
                                <td>Пять островов Греции</td>
                                <td><span class="badge bg-success-subtle text-success">Adventure</span></td>
                                <td>Сегодня, 15:00</td>
                                <td><span class="badge bg-success">Опубликовано</span></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <div class="col-xxl-4 col-sm-12 col-12">

        <!-- Parser status -->
        <div class="card mb-4">
            <div class="card-body pt-0">
                <div class="d-flex align-items-center justify-content-between py-3">
                    <div>
                        <h6 class="mb-1"><i class="bi bi-cloud-download-fill text-warning me-2"></i>Статус парсера</h6>
                        <p class="text-muted small mb-0">Последний проход источника</p>
                    </div>
                    <div class="text-end">
                            <span class="badge bg-warning-subtle text-warning fw-semibold rounded-pill px-3 py-2">
                                <i class="bi bi-arrow-repeat me-1"></i> Работает
                            </span>
                        <div class="small text-warning mt-1">
                            04:00, сегодня
                        </div>
                    </div>
                </div>
                <div class="progress medium" role="progressbar" aria-label="Parser" aria-valuenow="65"
                     aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar bg-warning w-60"></div>
                </div>
            </div>
        </div>

        <!-- Telegram connection -->
        <div class="card mb-4">
            <div class="card-body pt-0">
                <div class="d-flex align-items-center justify-content-between py-3">
                    <div>
                        <h6 class="mb-1"><i class="bi bi-telegram text-primary me-2"></i>Канал TRVL</h6>
                        <p class="text-muted small mb-0">Подключение к Telegram API</p>
                    </div>
                    <div class="text-end">
                        <?php if ($channelConnected): ?>
                            <span class="badge bg-success-subtle text-success fw-semibold rounded-pill px-3 py-2">
                                <i class="bi bi-check-circle me-1"></i> Подключен
                            </span>
                            <div class="small text-success mt-1">
                                Все системы работают.
                            </div>
                        <?php else: ?>
                            <span class="badge bg-secondary-subtle text-secondary fw-semibold rounded-pill px-3 py-2">
                                <i class="bi bi-plug me-1"></i> Не подключен
                            </span>
                            <div class="small text-secondary mt-1">
                                Задайте TELEGRAM_BOT_TOKEN в .env
                            </div>
                        <?php endif ?>
                    </div>
                </div>
                <div class="progress medium" role="progressbar" aria-label="Telegram"
                     aria-valuenow="<?= $channelConnected ? 90 : 10 ?>" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar <?= $channelConnected ? 'bg-success w-90' : 'bg-secondary w-10' ?>"></div>
                </div>
            </div>
        </div>

    </div>
</div>
<!-- Row end -->
