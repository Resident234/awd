<?php

/** @var yii\web\View $this */
/** @var \app\shared\Publications\Dto\PublicationData[] $deleted */
/** @var string $now */

?>
<?php if ($deleted === []): ?>
    <p class="text-muted small mb-0 py-3">
        <i class="bi bi-info-circle me-1"></i>
        Удаленных записей пока нет.
    </p>
<?php endif ?>
<?php foreach ($deleted as $record): ?>
    <?= $this->render('_item_deleted', ['record' => $record, 'now' => $now]) ?>
<?php endforeach ?>
