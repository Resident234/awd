<?php

/** @var yii\web\View $this */
/** @var array<int, array{topic: \app\shared\Forum\Dto\TopicData, posts: \app\shared\Forum\Dto\PostData[], postsTotal: int}> $topics */

?>
<?php if ($topics === []): ?>
    <p class="text-muted small mb-0 py-3">
        <i class="bi bi-info-circle me-1"></i>
        Топиков пока нет.
    </p>
<?php endif ?>
<?php foreach ($topics as $item): ?>
    <?= $this->render('_item_topic', ['record' => $item]) ?>
<?php endforeach ?>
