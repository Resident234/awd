<?php

/** @var yii\web\View $this */
/** @var \app\shared\Publications\Dto\PublicationData[] $drafts */
/** @var string $now */

?>
<?php if ($drafts === []): ?>
    <p class="text-muted small mb-0 py-3">
        <i class="bi bi-info-circle me-1"></i>
        Черновиков пока нет.
    </p>
<?php endif ?>
<?php foreach ($drafts as $draft): ?>
    <?= $this->render('_item_draft', ['record' => $draft, 'now' => $now]) ?>
<?php endforeach ?>
