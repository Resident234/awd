<?php

/** @var yii\web\View $this */
/** @var \app\shared\Publications\Dto\PublicationData[] $posts */
/** @var string $now */

?>
<?php if ($posts === []): ?>
    <p class="text-muted small mb-0 py-3">
        <i class="bi bi-info-circle me-1"></i>
        Публикаций пока нет.
    </p>
<?php endif ?>
<?php foreach ($posts as $post): ?>
    <?= $this->render('_item_post', ['record' => $post, 'now' => $now]) ?>
<?php endforeach ?>
