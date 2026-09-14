<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var \app\shared\Publications\Dto\PublicationData[] $posts */
/** @var \app\shared\Publications\Dto\PublicationData[] $drafts */
/** @var \app\shared\Publications\Dto\PublicationData[] $deleted */
/** @var array<int, array{topic: \app\shared\Forum\Dto\TopicData, posts: \app\shared\Forum\Dto\PostData[]}> $topics */
/** @var bool $withImagesOnly */
/** @var bool $withPostsOnly */
/** @var string $now */

use yii\helpers\Html;

$this->title = 'Публикации в канал';


$nextSlot = (int)ceil((time() + 60) / 600) * 600;
$defaultAt = gmdate('d/m/Y h:i A', $nextSlot);

$deleteForm = static function (int $id, string $source): string {
    $csrf = '<input type="hidden" name="' . Yii::$app->request->csrfParam
        . '" value="' . Yii::$app->request->csrfToken . '">';
    return '<form method="post" action="' . \yii\helpers\Url::to(['site/publication-delete'])
        . '" class="d-inline">' . $csrf
        . '<input type="hidden" name="publicationSource" value="' . $source . '">'
        . '<input type="hidden" name="publicationId" value="' . $id . '">'
        . '<button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3" '
        . 'title="Удалить"><i class="bi bi-trash"></i></button></form>';
};

/** @var \app\shared\Publications\Dto\PublicationData[] $duePosts */
$duePosts = array_values(array_filter(
    $posts,
    static fn (\app\shared\Publications\Dto\PublicationData $post): bool => $post->telegramId !== null,
));

$this->registerCss(
    <<<CSS
.forum-column {
    display: flex;
    flex-direction: column;
}
.forum-column > .card:last-child {
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
}
.forum-column > .card:last-child > .card-body {
    flex: 1 1 auto;
    min-height: 0;
    display: flex;
    flex-direction: column;
}
.forum-column .scroll350 {
    height: auto;
    flex: 1 1 0;
    min-height: 0;
}
CSS
);
?>
<!-- Row start -->
<div class="row">
    <div class="col-sm-6 col-6 forum-column">

        <!-- Forum filters -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title">Фильтры</h5>
            </div>
            <div class="card-body">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="forumFilterWithImages"
                           data-filter-url="<?= \yii\helpers\Url::to(['site/publications']) ?>"
                        <?= $withImagesOnly ? 'checked' : '' ?>>
                    <label class="form-check-label" for="forumFilterWithImages">С изображениями</label>
                </div>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="forumFilterWithPosts"
                           data-filter-url="<?= \yii\helpers\Url::to(['site/publications']) ?>"
                        <?= $withPostsOnly ? 'checked' : '' ?>>
                    <label class="form-check-label" for="forumFilterWithPosts">С привязанными постами</label>
                </div>
            </div>
            <div class="card-footer bg-transparent">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        <i class="bi bi-funnel me-1"></i>
                        Фильтры применяются к блоку «Форум»
                    </small>
                </div>
            </div>
        </div>

        <!-- Forum topics -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title">Форум</h5>
            </div>
            <div class="card-body">
                <div class="scroll350">

                    <!-- Forum topics widget start -->
                    <div class="notification-center h-100">
                        <div class="threads">
                            <?php if ($topics === []): ?>
                                <p class="text-muted small mb-0 py-3">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Топиков пока нет.
                                </p>
                            <?php endif ?>
                            <?php foreach ($topics as $item): ?>
                                <?php $topic = $item['topic']; ?>
                                <div class="thread mb-4 pb-3 border-bottom">
                                    <div class="d-flex align-items-start gap-3 mb-3">
                                        <?php if ($topic->author?->avatarUrl !== null): ?>
                                            <img src="<?= Html::encode($topic->author->avatarUrl) ?>"
                                                 class="rounded-circle img-3x flex-shrink-0"
                                                 alt="<?= Html::encode($topic->author->name) ?>">
                                        <?php else: ?>
                                            <span class="rounded-circle img-3x flex-shrink-0 bg-primary-subtle d-flex align-items-center justify-content-center">
                                                <i class="bi bi-person-fill text-primary"></i>
                                            </span>
                                        <?php endif ?>
                                        <div class="flex-grow-1">
                                            <div class="thread-header d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="fw-bold mb-0"><?= Html::encode($topic->title) ?></h6>
                                                <span class="d-flex align-items-center gap-1">
                                                    <?php if ($topic->publicationStatus !== null): ?>
                                                        <span class="badge <?= $topic->publicationStatus === 'published' ? 'bg-success' : 'bg-secondary' ?> rounded-pill">
                                                            <?= $topic->publicationStatus === 'published' ? 'Опубликовано' : 'Просмотрено' ?>
                                                        </span>
                                                    <?php endif ?>
                                                    <?php if ($topic->publicationTelegramId !== null): ?>
                                                        <span class="badge rounded-pill bg-dark" title="telegram_id">TG: <?= Html::encode($topic->publicationTelegramId) ?></span>
                                                    <?php endif ?>
                                                    <span class="badge bg-primary">#<?= $topic->id ?></span>
                                                </span>
                                            </div>
                                            <p class="mb-2" style="white-space: pre-line; word-break: break-word;"><?= Html::encode($topic->contentText) ?></p>
                                            <?php if ($topic->contentHtml !== ''): ?>
                                                <details class="mb-2">
                                                    <summary class="text-muted small">
                                                        <i class="bi bi-code-slash me-1"></i>HTML-исходник
                                                    </summary>
                                                    <pre class="small text-muted mb-0"
                                                         style="white-space: pre-wrap; word-break: break-word;"><?= Html::encode($topic->contentHtml) ?></pre>
                                                </details>
                                            <?php endif ?>
                                            <div class="thread-meta d-flex align-items-center text-muted small flex-wrap">
                                                <span class="me-3"><i class="bi bi-person"></i>
                                                    <?= Html::encode($topic->author?->name ?? 'Неизвестный автор') ?>
                                                </span>
                                                <span class="me-3"><i class="bi bi-clock"></i> <?= Html::encode($topic->publishedAt ?? '') ?></span>
                                                <span class="me-3"><i class="bi bi-chat-dots"></i> <?= count($item['posts']) ?></span>
                                            </div>
                                            <div class="thread-meta text-muted small mt-1">
                                                <i class="bi bi-link-45deg"></i>
                                                <a href="<?= Html::encode($topic->sourceUrl) ?>" target="_blank" rel="noopener"
                                                   class="text-muted"><?= Html::encode($topic->sourceUrl) ?></a>
                                            </div>
                                            <?php if ($topic->imageUrls !== []): ?>
                                                <div class="d-flex mt-2 flex-wrap align-items-start">
                                                    <?php foreach ($topic->imageUrls as $imageUrl): ?>
                                                        <a href="<?= Html::encode($imageUrl) ?>" target="_blank" rel="noopener" class="d-inline-block mb-3">
                                                            <img src="<?= Html::encode($imageUrl) ?>" class="img-3x rounded-2 me-3"
                                                                 style="object-fit: cover;"
                                                                 alt="Изображение темы">
                                                        </a>
                                                    <?php endforeach ?>
                                                </div>
                                                <details class="mt-1">
                                                    <summary class="text-muted small">
                                                        <i class="bi bi-link-45deg me-1"></i>Ссылки на изображения (<?= count($topic->imageUrls) ?>)
                                                    </summary>
                                                    <?php foreach ($topic->imageUrls as $imageUrl): ?>
                                                        <div class="text-muted small">
                                                            <a href="<?= Html::encode($imageUrl) ?>" target="_blank" rel="noopener"
                                                               class="text-muted" style="word-break: break-all;"><?= Html::encode($imageUrl) ?></a>
                                                        </div>
                                                    <?php endforeach ?>
                                                </details>
                                            <?php endif ?>
                                        </div>
                                    </div>
                                    <?php if ($item['posts'] !== []): ?>
                                        <div class="thread-replies ms-5">
                                            <?php foreach ($item['posts'] as $post): ?>
                                                <div class="reply d-flex gap-3 mb-3">
                                                    <?php if ($post->author?->avatarUrl !== null): ?>
                                                        <img src="<?= Html::encode($post->author->avatarUrl) ?>" class="rounded-circle img-2x"
                                                             alt="<?= Html::encode($post->author->name) ?>">
                                                    <?php else: ?>
                                                        <span class="rounded-circle img-2x bg-secondary-subtle d-flex align-items-center justify-content-center">
                                                            <i class="bi bi-person-fill text-secondary"></i>
                                                        </span>
                                                    <?php endif ?>
                                                    <div>
                                                        <?php if ($post->title !== ''): ?>
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <h6 class="fw-bold mb-0"><?= Html::encode($post->title) ?></h6>
                                                                <span class="d-flex align-items-center gap-1">
                                                                    <?php if ($post->publicationStatus !== null): ?>
                                                                        <span class="badge <?= $post->publicationStatus === 'published' ? 'bg-success' : 'bg-secondary' ?> rounded-pill">
                                                                            <?= $post->publicationStatus === 'published' ? 'Опубликовано' : 'Просмотрено' ?>
                                                                        </span>
                                                                    <?php endif ?>
                                                                    <?php if ($post->publicationTelegramId !== null): ?>
                                                                        <span class="badge rounded-pill bg-info text-dark" title="telegram_id">TG: <?= Html::encode($post->publicationTelegramId) ?></span>
                                                                    <?php endif ?>
                                                                </span>
                                                            </div>
                                                        <?php elseif ($post->publicationStatus !== null || $post->publicationTelegramId !== null): ?>
                                                            <span class="d-inline-block mb-1">
                                                                <?php if ($post->publicationStatus !== null): ?>
                                                                    <span class="badge <?= $post->publicationStatus === 'published' ? 'bg-success' : 'bg-secondary' ?> rounded-pill">
                                                                        <?= $post->publicationStatus === 'published' ? 'Опубликовано' : 'Просмотрено' ?>
                                                                    </span>
                                                                <?php endif ?>
                                                                <?php if ($post->publicationTelegramId !== null): ?>
                                                                    <span class="badge rounded-pill bg-info text-dark" title="telegram_id">TG: <?= Html::encode($post->publicationTelegramId) ?></span>
                                                                <?php endif ?>
                                                            </span>
                                                        <?php endif ?>
                                                        <p class="mb-1" style="white-space: pre-line; word-break: break-word;"><?= Html::encode($post->contentText) ?></p>
                                                        <?php if ($post->contentHtml !== ''): ?>
                                                            <details class="mb-1">
                                                                <summary class="text-muted small">
                                                                    <i class="bi bi-code-slash me-1"></i>HTML-исходник
                                                                </summary>
                                                                <pre class="small text-muted mb-0"
                                                                     style="white-space: pre-wrap; word-break: break-word;"><?= Html::encode($post->contentHtml) ?></pre>
                                                            </details>
                                                        <?php endif ?>
                                                        <small class="text-muted d-block">
                                                            <?= Html::encode($post->author?->name ?? 'Неизвестный автор') ?>
                                                            <?php if ($post->number !== null): ?>
                                                                • пост #<?= $post->number ?>
                                                            <?php endif ?>
                                                            <?php if ($post->postedAt !== null): ?>
                                                                • <?= Html::encode($post->postedAt) ?>
                                                            <?php endif ?>
                                                        </small>
                                                        <small class="text-muted d-block mt-1">
                                                            <a href="<?= Html::encode($post->sourceUrl) ?>" target="_blank" rel="noopener"
                                                               class="text-muted"><?= Html::encode($post->sourceUrl) ?></a>
                                                        </small>
                                                        <?php if ($post->imageUrls !== []): ?>
                                                            <div class="d-flex mt-2 flex-wrap align-items-start">
                                                                <?php foreach ($post->imageUrls as $imageUrl): ?>
                                                                    <a href="<?= Html::encode($imageUrl) ?>" target="_blank" rel="noopener" class="d-inline-block mb-3">
                                                                        <img src="<?= Html::encode($imageUrl) ?>" class="img-3x rounded-2 me-3"
                                                                             style="object-fit: cover;"
                                                                             alt="Изображение поста">
                                                                    </a>
                                                                <?php endforeach ?>
                                                            </div>
                                                            <details class="mt-1">
                                                                <summary class="text-muted small">
                                                                    <i class="bi bi-link-45deg me-1"></i>Ссылки на изображения (<?= count($post->imageUrls) ?>)
                                                                </summary>
                                                                <?php foreach ($post->imageUrls as $imageUrl): ?>
                                                                    <div class="text-muted small">
                                                                        <a href="<?= Html::encode($imageUrl) ?>" target="_blank" rel="noopener"
                                                                           class="text-muted" style="word-break: break-all;"><?= Html::encode($imageUrl) ?></a>
                                                                    </div>
                                                                <?php endforeach ?>
                                                            </details>
                                                        <?php endif ?>
                                                    </div>
                                                </div>
                                            <?php endforeach ?>
                                        </div>
                                    <?php endif ?>
                                </div>
                            <?php endforeach ?>
                        </div>
                    </div>
                    <!-- Forum topics widget end -->

                </div>
            </div>
        </div>

    </div>
    <div class="col-sm-6 col-6">

        <!-- Publication preview -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title">Предпросмотр публикации</h5>
            </div>
            <div class="card-body">
                <p class="mb-0" id="publicationPreview" data-source="publicationTextInput">
                    Введите текст публикации — он отобразится здесь до отправки в канал TRVL.
                </p>
            </div>
            <div class="card-footer bg-transparent">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        <i class="bi bi-eye me-1"></i>
                        Текст обновляется по мере ввода
                    </small>
                </div>
            </div>
        </div>

        <!-- New post form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title">Новая публикация</h5>
            </div>
            <div class="card-body">
                <form method="post" action="<?= \yii\helpers\Url::to(['site/publication-create']) ?>">
                    <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>"
                           value="<?= Yii::$app->request->csrfToken ?>">
                    <input type="hidden" name="publicationSource" id="publicationSource" value="new">
                    <input type="hidden" name="publicationSourceId" id="publicationSourceId" value="">

                    <!-- Textarea -->
                    <div class="mb-3">
                        <label for="publicationTextInput" class="form-label">Текст публикации</label>
                        <textarea class="form-control" id="publicationTextInput" name="publicationText"
                                  rows="6"
                                  maxlength="4096"
                                  placeholder="Введите текст публикации"></textarea>
                    </div>

                    <!-- Publication date & time -->
                    <div class="mb-3">
                        <label class="form-label" for="publicationAt">Дата и время публикации</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-calendar4"></i>
                            </span>
                            <input type="text" id="publicationAt" name="publicationAt"
                                   class="form-control datepicker-time"
                                   value="<?= Html::encode($defaultAt) ?>">
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" name="action" value="publish" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i>Опубликовать
                        </button>
                        <button type="submit" name="action" value="draft" class="btn btn-outline-secondary">
                            <i class="bi bi-save me-1"></i>Сохранить
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer bg-transparent">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        Запись сохраняется в БД и будет отправлена в канал TRVL в заданное время
                    </small>
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3">4096 символов</span>
                </div>
            </div>
        </div>

    </div>

    <div class="col-sm-4">

        <!-- Publications -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title">Публикации</h5>
            </div>
            <div class="card-body">
                <div class="scroll350">

                    <!-- Timeline start -->
                    <div class="m-0">
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
                            <?php
                            $postPublishedAt = '';
                            if ($post->publishedAt !== null) {
                                $postDate = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $post->publishedAt, new DateTimeZone('UTC'));
                                $postPublishedAt = $postDate instanceof DateTimeImmutable ? $postDate->format('d/m/Y h:i A') : '';
                            }
                            ?>
                            <div class="activity-log" data-text="<?= Html::encode($post->text) ?>"
                                 data-source-type="post" data-source-id="<?= $post->id ?>"
                                 data-published-at="<?= Html::encode($postPublishedAt) ?>">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <?php if ($post->telegramId !== null): ?>
                                        <p class="mb-0">
                                            <span class="text-primary">#<?= $post->telegramId ?></span>
                                        </p>
                                    <?php endif ?>
                                    <a href="#" class="btn btn-sm btn-outline-primary rounded-pill px-3" title="Редактировать">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <?= $deleteForm($post->id, 'post') ?>
                                    <?php if (!$isPublished): ?>
                                        <form method="post" action="<?= \yii\helpers\Url::to(['site/publication-publish']) ?>"
                                              class="d-inline">
                                            <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>"
                                                   value="<?= Yii::$app->request->csrfToken ?>">
                                            <input type="hidden" name="publicationSource" value="post">
                                            <input type="hidden" name="publicationId" value="<?= $post->id ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-3"
                                                    title="Опубликовать">
                                                <i class="bi bi-send"></i>
                                            </button>
                                        </form>
                                    <?php endif ?>
                                    <form method="post" action="<?= \yii\helpers\Url::to(['site/publication-to-draft']) ?>"
                                          class="d-inline" data-no-edit="1">
                                        <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>"
                                               value="<?= Yii::$app->request->csrfToken ?>">
                                        <input type="hidden" name="publicationId" value="<?= $post->id ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-warning rounded-pill px-3"
                                                title="Переместить в черновик">
                                            <i class="bi bi-file-earmark-arrow-down"></i>
                                        </button>
                                    </form>
                                </div>
                                <p class="mb-1"><?= Html::encode($post->text) ?></p>
                                <div class="activity-meta">
                                    <i class="bi bi-clock me-1"></i><?= Html::encode($post->publishedAt ?? '') ?>
                                </div>
                                <span class="badge <?= $isPublished ? 'bg-success' : 'bg-info' ?> mt-2">
                                    <?= $isPublished ? 'Опубликовано' : 'Запланировано' ?>
                                </span>
                                <span class="badge bg-warning text-dark mt-2 d-none editing-badge">Редактируется</span>
                            </div>
                        <?php endforeach ?>
                    </div>
                    <!-- Timeline end -->

                </div>
            </div>
        </div>

    </div>
    <div class="col-sm-4">

        <!-- Drafts -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title">Черновики</h5>
            </div>
            <div class="card-body">
                <div class="scroll350">

                    <!-- Timeline start -->
                    <div class="m-0">
                        <?php if ($drafts === []): ?>
                            <p class="text-muted small mb-0 py-3">
                                <i class="bi bi-info-circle me-1"></i>
                                Черновиков пока нет.
                            </p>
                        <?php endif ?>
                        <?php foreach ($drafts as $draft): ?>
                            <div class="activity-log" data-text="<?= Html::encode($draft->text) ?>"
                                 data-source-type="draft" data-source-id="<?= $draft->id ?>">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <a href="#" class="btn btn-sm btn-outline-primary rounded-pill px-3" title="Редактировать">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <?= $deleteForm($draft->id, 'draft') ?>
                                    <form method="post" action="<?= \yii\helpers\Url::to(['site/publication-publish']) ?>"
                                          class="d-inline">
                                        <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>"
                                               value="<?= Yii::$app->request->csrfToken ?>">
                                        <input type="hidden" name="publicationSource" value="draft">
                                        <input type="hidden" name="publicationId" value="<?= $draft->id ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-3"
                                                title="Опубликовать">
                                            <i class="bi bi-send"></i>
                                        </button>
                                    </form>
                                    <a href="#" class="btn btn-sm btn-outline-info rounded-pill px-3" title="Запланировать публикацию"
                                       data-bs-toggle="modal" data-bs-target="#scheduleModal"
                                       data-source="draft" data-draft-id="<?= $draft->id ?>">
                                        <i class="bi bi-calendar2-plus"></i>
                                    </a>
                                </div>
                                <p class="mb-1"><?= Html::encode($draft->text) ?></p>
                                <span class="badge bg-secondary mt-2">Черновик</span>
                                <span class="badge bg-warning text-dark mt-2 d-none editing-badge">Редактируется</span>
                            </div>
                        <?php endforeach ?>
                    </div>
                    <!-- Timeline end -->

                </div>
            </div>
        </div>

    </div>
    <div class="col-sm-4">

        <!-- Deleted -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title">Удаленные</h5>
            </div>
            <div class="card-body">
                <div class="scroll350">

                    <!-- Timeline start -->
                    <div class="m-0">
                        <?php if ($deleted === []): ?>
                            <p class="text-muted small mb-0 py-3">
                                <i class="bi bi-info-circle me-1"></i>
                                Удаленных записей пока нет.
                            </p>
                        <?php endif ?>
                        <?php foreach ($deleted as $deletedRecord): ?>
                            <?php
                            $deletedPublishedAt = '';
                            if ($deletedRecord->publishedAt !== null) {
                                $deletedDate = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $deletedRecord->publishedAt, new DateTimeZone('UTC'));
                                $deletedPublishedAt = $deletedDate instanceof DateTimeImmutable ? $deletedDate->format('d/m/Y h:i A') : '';
                            }
                            ?>
                            <div class="activity-log" data-text="<?= Html::encode($deletedRecord->text) ?>"
                                 data-source-type="deleted" data-source-id="<?= $deletedRecord->id ?>"
                                 data-published-at="<?= Html::encode($deletedPublishedAt) ?>">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <a href="#" class="btn btn-sm btn-outline-primary rounded-pill px-3" title="Редактировать">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form method="post" action="<?= \yii\helpers\Url::to(['site/publication-publish']) ?>"
                                          class="d-inline">
                                        <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>"
                                               value="<?= Yii::$app->request->csrfToken ?>">
                                        <input type="hidden" name="publicationSource" value="deleted">
                                        <input type="hidden" name="publicationId" value="<?= $deletedRecord->id ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-3"
                                                title="Опубликовать">
                                            <i class="bi bi-send"></i>
                                        </button>
                                    </form>
                                    <a href="#" class="btn btn-sm btn-outline-info rounded-pill px-3" title="Запланировать публикацию"
                                       data-bs-toggle="modal" data-bs-target="#scheduleModal"
                                       data-source="deleted" data-draft-id="<?= $deletedRecord->id ?>">
                                        <i class="bi bi-calendar2-plus"></i>
                                    </a>
                                    <form method="post" action="<?= \yii\helpers\Url::to(['site/publication-to-draft']) ?>"
                                          class="d-inline" data-no-edit="1">
                                        <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>"
                                               value="<?= Yii::$app->request->csrfToken ?>">
                                        <input type="hidden" name="publicationSource" value="deleted">
                                        <input type="hidden" name="publicationId" value="<?= $deletedRecord->id ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-warning rounded-pill px-3"
                                                title="Перенести в черновик">
                                            <i class="bi bi-file-earmark-arrow-down"></i>
                                        </button>
                                    </form>
                                </div>
                                <p class="mb-1"><?= Html::encode($deletedRecord->text) ?></p>
                                <div class="activity-meta">
                                    <i class="bi bi-clock me-1"></i><?= Html::encode($deletedRecord->publishedAt ?? '') ?>
                                </div>
                                <span class="badge <?= $deletedRecord->deletedAt === null ? 'bg-danger' : 'bg-dark' ?> mt-2">
                                    <?= $deletedRecord->deletedAt === null ? 'Удалено' : 'Удалено из канала' ?>
                                </span>
                                <span class="badge bg-warning text-dark mt-2 d-none editing-badge">Редактируется</span>
                            </div>
                        <?php endforeach ?>
                    </div>
                    <!-- Timeline end -->

                </div>
            </div>
        </div>

    </div>
</div>
<!-- Row end -->

<!-- Schedule modal -->
<div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scheduleModalLabel">
                    Запланировать публикацию
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="<?= \yii\helpers\Url::to(['site/publication-schedule']) ?>"
                      id="scheduleForm">
                    <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>"
                           value="<?= Yii::$app->request->csrfToken ?>">
                    <input type="hidden" name="publicationSource" id="scheduleSource" value="draft">
                    <input type="hidden" name="publicationId" id="scheduleDraftId" value="">
                    <div class="mb-3">
                        <label class="form-label" for="scheduleAt">Дата и время публикации</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-calendar4"></i>
                            </span>
                            <input type="text" id="scheduleAt" name="publicationAt"
                                   class="form-control datepicker-time" value="<?= Html::encode($defaultAt) ?>">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Отмена
                </button>
                <button type="submit" form="scheduleForm" class="btn btn-primary">
                    <i class="bi bi-calendar2-plus me-1"></i>Запланировать
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$this->registerJs(
    <<<JS
(function () {
    var applyFilters = function () {
        var imagesSwitch = document.getElementById('forumFilterWithImages');
        var postsSwitch = document.getElementById('forumFilterWithPosts');
        var base = (imagesSwitch || postsSwitch).getAttribute('data-filter-url');
        var params = [];
        if (imagesSwitch && imagesSwitch.checked) {
            params.push('withImages=1');
        }
        if (postsSwitch && postsSwitch.checked) {
            params.push('withPosts=1');
        }
        window.location.href = params.length === 0
            ? base
            : base + (base.indexOf('?') === -1 ? '?' : '&') + params.join('&');
    };
    ['forumFilterWithImages', 'forumFilterWithPosts'].forEach(function (id) {
        var element = document.getElementById(id);
        if (element) {
            element.addEventListener('change', applyFilters);
        }
    });

    var source = document.getElementById('publicationTextInput');
    if (!source) {
        return;
    }
    var preview = document.getElementById('publicationPreview');
    var update = function () {
        if (preview) {
            preview.textContent = source.value || source.placeholder;
        }
    };
    source.addEventListener('input', update);
    update();

    var scrollToMiddle = function (log) {
        var scroller = log.closest('.scroll350');
        if (!scroller) {
            return;
        }
        var viewport = scroller.querySelector('.os-viewport') || scroller;
        var logRect = log.getBoundingClientRect();
        var viewRect = viewport.getBoundingClientRect();
        var delta = logRect.top + logRect.height / 2 - (viewRect.top + viewRect.height / 2);
        viewport.scrollTop += delta;
        if (scroller.scrollTop !== undefined && scroller !== viewport) {
            scroller.scrollTop += delta;
        }
    };

    var editingLog = null;

    var sourceTypeInput = document.getElementById('publicationSource');
    var sourceIdInput = document.getElementById('publicationSourceId');
    var publishedAtInput = document.getElementById('publicationAt');

    var setEditing = function (log) {
        if (editingLog === log) {
            return;
        }
        if (editingLog) {
            editingLog.querySelector('.editing-badge').classList.add('d-none');
        }
        editingLog = log;
        log.querySelector('.editing-badge').classList.remove('d-none');
        source.value = log.getAttribute('data-text') || '';
        source.dispatchEvent(new Event('input'));
        scrollToMiddle(log);

        if (sourceTypeInput && sourceIdInput) {
            sourceTypeInput.value = log.getAttribute('data-source-type') || 'new';
            sourceIdInput.value = log.getAttribute('data-source-id') || '';
        }
        if (publishedAtInput) {
            publishedAtInput.value = log.getAttribute('data-published-at') || publishedAtInput.value;
        }
    };

    var resetForm = document.querySelector('form[action*="publication-create"]');
    if (resetForm) {
        resetForm.addEventListener('submit', function () {
            if (sourceTypeInput && sourceIdInput && sourceTypeInput.value === 'new') {
                sourceIdInput.value = '';
            }
        });
    }

    document.querySelectorAll('.activity-log').forEach(function (log) {
        log.addEventListener('dblclick', function () {
            setEditing(log);
        });
        log.querySelectorAll('a[title="Редактировать"]').forEach(function (btn) {
            btn.addEventListener('click', function (event) {
                event.preventDefault();
                setEditing(log);
            });
        });
    });

    var scheduleModal = document.getElementById('scheduleModal');
    if (scheduleModal) {
        scheduleModal.addEventListener('show.bs.modal', function (event) {
            var trigger = event.relatedTarget;
            var recordId = trigger ? trigger.getAttribute('data-draft-id') || '' : '';
            var source = trigger ? trigger.getAttribute('data-source') || 'draft' : 'draft';
            var recordIdInput = document.getElementById('scheduleDraftId');
            var sourceInput = document.getElementById('scheduleSource');
            if (recordIdInput) {
                recordIdInput.value = recordId;
            }
            if (sourceInput) {
                sourceInput.value = source;
            }
        });
    }
})();
JS
);
?>
