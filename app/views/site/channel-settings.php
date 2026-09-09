<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string|null $channelDescription */
/** @var \app\shared\Telegram\Dto\PublishedDescriptionData[] $publishedDescriptions */

use yii\helpers\Html;

$this->title = 'Настройки канала';
?>
<!-- Row start -->
<div class="row">
    <div class="col-xxl-7 col-sm-12 col-12">

        <!-- Channel description management -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title">Описание канала</h5>
            </div>
            <div class="card-body">
                <form method="post" action="<?= \yii\helpers\Url::to(['site/channel-description']) ?>">
                    <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>"
                           value="<?= Yii::$app->request->csrfToken ?>">

                    <!-- Description input field -->
                    <div class="mb-3">
                        <textarea class="form-control" id="channelDescriptionInput" name="channelDescription"
                                  rows="4"
                                  maxlength="255"
                                  aria-label="Описание канала"
                                  placeholder="Введите описание канала TRVL"><?= Html::encode($channelDescription ?? 'TRVL — канал о путешествиях и приключениях. Маршруты, лайфхаки и вдохновение для ваших странствий.') ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2 me-1"></i>Сохранить описание
                        </button>
                        <button type="button" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Сбросить
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer bg-transparent">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        Описание будет применено к каналу TRVL
                    </small>
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3">255 символов</span>
                </div>
            </div>
        </div>

    </div>
    <div class="col-xxl-5 col-sm-12 col-12">

        <!-- Description archive -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title">Архив описаний</h5>
            </div>
            <div class="card-body pt-0">
                <?php if ($publishedDescriptions === []): ?>
                    <p class="text-muted small mb-0 py-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Описания пока не публиковались.
                    </p>
                <?php else: ?>
                    <div class="scroll300">
                        <ul class="list-group">
                            <?php foreach ($publishedDescriptions as $publishedDescription): ?>
                                <?php $isActive = $publishedDescription->publishedTo === null; ?>
                                <li class="list-group-item d-flex align-items-center gap-3">
                                    <div class="icon-box md <?= $isActive
                                        ? 'bg-primary-subtle text-primary rounded-4'
                                        : 'bg-secondary-subtle text-secondary rounded-4' ?>">
                                        <i class="bi bi-quote"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="small mb-1"><?= Html::encode($publishedDescription->description !== '' ? $publishedDescription->description : '—') ?></div>
                                        <small class="text-muted">с <?= $publishedDescription->publishedFrom ?><?= $isActive ? '' : ' по ' . $publishedDescription->publishedTo ?></small>
                                    </div>
                                    <span class="badge <?= $isActive
                                        ? 'bg-success-subtle text-success rounded-pill py-1 px-3'
                                        : 'bg-secondary-subtle text-secondary rounded-pill py-1 px-3' ?>">
                                        <?= $isActive ? 'Активен' : 'Архив' ?>
                                    </span>
                                </li>
                            <?php endforeach ?>
                        </ul>
                    </div>
                <?php endif ?>
            </div>
        </div>

    </div>
</div>
<!-- Row end -->
