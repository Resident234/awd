<?php

/** @var yii\web\View $this */
/** @var \app\shared\Publications\Dto\PublicationData[] $drafts */

use app\widgets\PublicationsUi;
use yii\helpers\Html;

?>
<?php if ($drafts === []): ?>
    <p class="text-muted small mb-0 py-3">
        <i class="bi bi-info-circle me-1"></i>
        Черновиков пока нет.
    </p>
<?php endif ?>
<?php foreach ($drafts as $draft): ?>
    <div class="activity-log" data-text="<?= Html::encode($draft->text) ?>"
         data-source-type="draft" data-source-id="<?= $draft->id ?>"
         data-image-urls="<?= Html::encode(implode("\n", $draft->imageUrls)) ?>">
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="#" class="btn btn-sm btn-outline-primary rounded-pill px-3" title="Редактировать">
                <i class="bi bi-pencil-square"></i>
            </a>
            <?= PublicationsUi::deleteForm($draft->id, 'draft') ?>
            <?= PublicationsUi::publishForm($draft->id, 'draft') ?>
            <?= PublicationsUi::scheduleButton($draft->id, 'draft') ?>
        </div>
        <p class="mb-1"><?= Html::encode($draft->text) ?></p>
        <?= PublicationsUi::stackedImages($draft->imageUrls) ?>
        <div class="activity-meta">
            <i class="bi bi-clock me-1"></i><span class="utc-time" data-utc="<?= Html::encode($draft->createdAt ?? '') ?>"></span>
        </div>
        <span class="badge bg-secondary mt-2">Черновик</span>
        <span class="badge bg-warning text-dark mt-2 d-none editing-badge">Редактируется</span>
    </div>
<?php endforeach ?>
