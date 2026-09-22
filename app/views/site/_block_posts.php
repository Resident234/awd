<?php

/** @var yii\web\View $this */
/** @var \app\shared\Publications\Dto\PublicationData[] $posts */
/** @var string $now */

use app\widgets\PublicationsUi;
use yii\helpers\Html;

?>
<?php if ($posts === []): ?>
    <p class="text-muted small mb-0 py-3">
        <i class="bi bi-info-circle me-1"></i>
        Публикаций пока нет.
    </p>
<?php endif ?>
<?php foreach ($posts as $post): ?>
    <?php
    $isPublished = $post->telegramId !== null || ($post->publishedAt !== null && $post->publishedAt <= $now);
    ?>
    <div class="activity-log" data-text="<?= Html::encode($post->text) ?>"
         data-source-type="post" data-source-id="<?= $post->id ?>"
         data-published-at-utc="<?= Html::encode($post->publishedAt ?? '') ?>"
         data-image-urls="<?= Html::encode(implode("\n", $post->imageUrls)) ?>">
        <div class="d-flex align-items-center gap-2 mb-1">
            <?php if ($post->telegramId !== null): ?>
                <p class="mb-0">
                    <span class="text-primary">#<?= $post->telegramId ?></span>
                </p>
            <?php endif ?>
            <a href="#" class="btn btn-sm btn-outline-primary rounded-pill px-3" title="Редактировать">
                <i class="bi bi-pencil-square"></i>
            </a>
            <?= PublicationsUi::deleteForm($post->id, 'post') ?>
            <?php if (!$isPublished): ?>
                <?= PublicationsUi::publishForm($post->id, 'post') ?>
            <?php endif ?>
            <?= PublicationsUi::toDraftForm($post->id) ?>
        </div>
        <p class="mb-1" style="white-space: pre-line; word-break: break-word;"><?= Html::encode($post->text) ?></p>
        <?= PublicationsUi::stackedImages($post->imageUrls) ?>
        <div class="activity-meta">
            <i class="bi bi-clock me-1"></i><span class="utc-time" data-utc="<?= Html::encode($post->publishedAt ?? '') ?>"></span>
        </div>
        <span class="badge <?= $isPublished ? 'bg-success' : 'bg-info' ?> mt-2">
            <?= $isPublished ? 'Опубликовано' : 'Запланировано' ?>
        </span>
        <span class="badge bg-warning text-dark mt-2 d-none editing-badge">Редактируется</span>
    </div>
<?php endforeach ?>
