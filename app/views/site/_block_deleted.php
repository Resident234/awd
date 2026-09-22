<?php

/** @var yii\web\View $this */
/** @var \app\shared\Publications\Dto\PublicationData[] $deleted */

use app\widgets\PublicationsUi;
use yii\helpers\Html;

?>
<?php if ($deleted === []): ?>
    <p class="text-muted small mb-0 py-3">
        <i class="bi bi-info-circle me-1"></i>
        Удаленных записей пока нет.
    </p>
<?php endif ?>
<?php foreach ($deleted as $deletedRecord): ?>
    <div class="activity-log" data-text="<?= Html::encode($deletedRecord->text) ?>"
         data-source-type="deleted" data-source-id="<?= $deletedRecord->id ?>"
         data-published-at-utc="<?= Html::encode($deletedRecord->publishedAt ?? '') ?>"
         data-image-urls="<?= Html::encode(implode("\n", $deletedRecord->imageUrls)) ?>">
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="#" class="btn btn-sm btn-outline-primary rounded-pill px-3" title="Редактировать">
                <i class="bi bi-pencil-square"></i>
            </a>
            <?= PublicationsUi::publishForm($deletedRecord->id, 'deleted') ?>
            <?= PublicationsUi::scheduleButton($deletedRecord->id, 'deleted') ?>
            <?= PublicationsUi::toDraftForm($deletedRecord->id, 'deleted', 'Перенести в черновик') ?>
        </div>
        <p class="mb-1" style="white-space: pre-line; word-break: break-word;"><?= Html::encode($deletedRecord->text) ?></p>
        <?= PublicationsUi::stackedImages($deletedRecord->imageUrls) ?>
        <div class="activity-meta">
            <i class="bi bi-clock me-1"></i><span class="utc-time" data-utc="<?= Html::encode($deletedRecord->publishedAt ?? '') ?>"></span>
        </div>
        <span class="badge <?= $deletedRecord->deletedAt === null ? 'bg-danger' : 'bg-dark' ?> mt-2">
            <?= $deletedRecord->deletedAt === null ? 'Удалено' : 'Удалено из канала' ?>
        </span>
        <span class="badge bg-warning text-dark mt-2 d-none editing-badge">Редактируется</span>
    </div>
<?php endforeach ?>
