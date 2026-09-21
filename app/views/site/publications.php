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

$viewedForm = static function (int $id, string $type): string {
    $csrf = '<input type="hidden" name="' . Yii::$app->request->csrfParam
        . '" value="' . Yii::$app->request->csrfToken . '">';
    return '<form method="post" action="' . \yii\helpers\Url::to(['site/forum-viewed'])
        . '" class="d-inline">' . $csrf
        . '<input type="hidden" name="forumEntityType" value="' . $type . '">'
        . '<input type="hidden" name="forumEntityId" value="' . $id . '">'
        . '<button type="submit" class="btn btn-outline-primary btn-sm">'
        . '<i class="bi bi-check2-square me-1"></i>Просмотрено</button></form>';
};

$publishButton = static function (string $text, string $forumType, int $forumId, array $imageUrls = [], string $title = ''): string {
    $imagesAttr = $imageUrls === [] ? '' : ' data-image-urls="' . Html::encode(implode("\n", $imageUrls)) . '"';
    $titleAttr = $title !== '' ? ' data-title="' . Html::encode($title) . '"' : '';
    return '<button type="button" class="btn btn-outline-primary btn-sm forum-publish-btn" data-text="'
        . Html::encode($text) . '" data-forum-type="' . $forumType . '" data-forum-id="' . $forumId . '"'
        . $imagesAttr . $titleAttr . '>'
        . '<i class="bi bi-send me-1"></i>Опубликовать</button>';
};

$stackedImages = static function (array $imageUrls, int $limit = 4): string {
    if ($imageUrls === []) {
        return '';
    }

    $shown = array_slice($imageUrls, 0, $limit);
    $html = '<div class="stacked-images sm mt-2">';
    foreach ($shown as $url) {
        $html .= '<img src="' . Html::encode($url) . '" alt="Изображение публикации">';
    }
    $rest = count($imageUrls) - count($shown);
    if ($rest > 0) {
        $html .= '<span class="plus bg-danger">+' . $rest . '</span>';
    }
    $html .= '</div>';

    return $html;
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

/* Stacked images in preview */
#publicationPreviewImages.stacked-images {
    margin-top: 0.5rem;
}

#publicationPreviewImages.stacked-images img {
    max-height: 120px;
    width: auto;
    border-radius: 0.375rem;
    object-fit: cover;
}

#publicationPreviewImages.stacked-images .plus {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 60px;
    height: 60px;
    font-size: 1.25rem;
    font-weight: 600;
    border-radius: 0.375rem;
    background-color: var(--bs-danger);
    color: white;
    margin-left: 0.25rem;
}

.preview-card > .card-body {
    align-items: flex-start;
    justify-content: flex-start;
    display: flex;
    flex-direction: column;
}

#publicationPreview {
    margin-top: 0;
    padding-top: 0;
    align-self: flex-start;
    text-align: left;
    width: 100%;
    flex: 0 0 auto;
}

.telegram-preview-text {
    align-self: flex-start;
    width: 100%;
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
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="forumFilterWithPosts"
                           data-filter-url="<?= \yii\helpers\Url::to(['site/publications']) ?>"
                        <?= $withPostsOnly ? 'checked' : '' ?>>
                    <label class="form-check-label" for="forumFilterWithPosts">С привязанными постами</label>
                </div>
                <div class="mb-0">
                    <label for="forumFilterImagesCount" class="form-label small">
                        Кол-во изображений
                        <span id="forumFilterImagesCountValue" class="ms-2 fw-bold text-primary"><?= $imagesCount > 0 ? (int)$imagesCount : '∞' ?></span>
                    </label>
                    <input type="range" class="form-range" id="forumFilterImagesCount"
                           data-filter-url="<?= \yii\helpers\Url::to(['site/publications']) ?>"
                           min="0" max="100" value="<?= $imagesCount > 0 ? (int)$imagesCount : 0 ?>">
                    <div class="form-text">0 — без ограничения</div>
                </div>
            </div>
            <div class="card-footer bg-transparent">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        <i class="bi bi-funnel me-1"></i>
                        Фильтры применяются к блоку «Форум»
                    </small>
                    <?php
                        $clearUrl = \yii\helpers\Url::to(['site/forum-filter-clear']);
                    ?>
                    <a href="<?= $clearUrl ?>" class="btn btn-sm btn-outline-secondary" id="forumFilterClearBtn"
                       title="Очистить все фильтры">
                        <i class="bi bi-x-circle me-1"></i>Очистить
                    </a>
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
                                                <span class="me-3"><i class="bi bi-clock"></i> <?php
                                                    $userTz = $this->context->getUserDisplayTimezone();
                                                    $moscowTz = new DateTimeZone($userTz);
                                                    if ($topic->publishedAt !== null && $topic->publishedAt !== '') {
                                                        $tDate = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $topic->publishedAt, new DateTimeZone('UTC'));
                                                        if ($tDate instanceof DateTimeImmutable) {
                                                            echo Html::encode($tDate->setTimezone($moscowTz)->format('d.m.Y H:i'));
                                                        } else {
                                                            echo Html::encode($topic->publishedAt);
                                                        }
                                                    } else {
                                                        echo '';
                                                    }
                                                ?></span>
                                                <span class="me-3"><i class="bi bi-chat-dots"></i> <?= count($item['posts']) ?></span>
                                            </div>
                                            <div class="thread-meta text-muted small mt-1">
                                                <i class="bi bi-link-45deg"></i>
                                                <a href="<?= Html::encode($topic->sourceUrl) ?>" target="_blank" rel="noopener"
                                                   class="text-muted"><?= Html::encode($topic->sourceUrl) ?></a>
                                            </div>
                                            <div class="d-flex align-items-center gap-2 mt-2 mb-1 flex-wrap">
                                                <?= $viewedForm($topic->id, 'topic') ?>
                                                <?= $publishButton($topic->contentText, 'topic', $topic->id, $topic->imageUrls, $topic->title) ?>
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
                                                        <img src="<?= Html::encode($post->author->avatarUrl) ?>" class="rounded-circle img-2x flex-shrink-0"
                                                             alt="<?= Html::encode($post->author->name) ?>">
                                                    <?php else: ?>
                                                        <span class="rounded-circle img-2x flex-shrink-0 bg-secondary-subtle d-flex align-items-center justify-content-center">
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
                                                        <div class="d-flex align-items-center gap-2 mt-2 mb-1 flex-wrap">
                                                            <?= $viewedForm($post->id, 'post') ?>
                                                            <?= $publishButton($post->contentText, 'post', $post->id, $post->imageUrls, $post->title) ?>
                                                        </div>
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
        <div class="card mb-4 preview-card">
            <div class="card-header">
                <h5 class="card-title text-primary">Предпросмотр публикации</h5>
            </div>
            <div class="card-img">
                <img src="" class="card-img-top img-fluid d-none" alt="Превью" id="previewCardImgEl">
            </div>
            <div class="card-body">
                <p class="mb-4" id="publicationPreview" data-source="publicationTextInput">Введите текст публикации — он отобразится здесь до отправки в канал TRVL.</p>
                <div class="stacked-images mt-2 d-none" id="publicationPreviewImages"></div>
            </div>
            <div class="card-footer bg-transparent">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        <i class="bi bi-eye me-1"></i>
                        Текст обновляется по мере ввода
                    </small>
                    <span id="previewPublicationAt" class="badge bg-primary-subtle text-primary rounded-pill px-3"></span>
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3">4096 символов</span>
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
                    <input type="hidden" name="forumEntityType" id="forumEntityType" value="">
                    <input type="hidden" name="forumEntityId" id="forumEntityId" value="">
                    <input type="hidden" name="publicationTz" id="publicationTz" value="">
                    

                    <!-- Textarea -->
                    <div class="mb-3">
                        <label for="publicationTextInput" class="form-label">Текст публикации</label>
                        <textarea class="form-control" id="publicationTextInput" name="publicationText"
                                  maxlength="4096"
                                  placeholder="Введите текст публикации"></textarea>
                    </div>

                    <!-- Attached images -->
                    <div class="mb-3">
                        <label for="publicationImages" class="form-label">
                            <i class="bi bi-images me-1"></i>Изображения публикации
                        </label>
                        <textarea class="form-control" id="publicationImages" name="publicationImages"
                                  rows="3"
                                  placeholder="По одному URL изображения в строке&#10;https://example.com/photo1.jpg&#10;https://example.com/photo2.jpg"></textarea>
                        <div class="stacked-images mt-2 d-none" id="publicationImagesPreview"></div>
                        <small class="text-muted">
                            Изображения отправляются в канал вместе с текстом публикации (первое — с подписью)
                        </small>
                    </div>

                    <!-- Publication date & time -->
                    <div class="mb-3">
                        <label class="form-label" for="publicationAt">Дата и время публикации</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-calendar4"></i>
                            </span>
                            <input type="text" id="publicationAt" name="publicationAt"
                                   class="form-control publication-datepicker-time"
                                   autocomplete="off">
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
                                <?= $stackedImages($post->imageUrls) ?>
                                <div class="activity-meta">
                                    <i class="bi bi-clock me-1"></i><span class="utc-time" data-utc="<?= Html::encode($post->publishedAt ?? '') ?>"></span>
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
                                 data-source-type="draft" data-source-id="<?= $draft->id ?>"
                                 data-image-urls="<?= Html::encode(implode("\n", $draft->imageUrls)) ?>">
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
                                <?= $stackedImages($draft->imageUrls) ?>
                                <div class="activity-meta">
                                    <i class="bi bi-clock me-1"></i><span class="utc-time" data-utc="<?= Html::encode($draft->createdAt ?? '') ?>"></span>
                                </div>
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
                            <div class="activity-log" data-text="<?= Html::encode($deletedRecord->text) ?>"
                                 data-source-type="deleted" data-source-id="<?= $deletedRecord->id ?>"
                                 data-published-at-utc="<?= Html::encode($deletedRecord->publishedAt ?? '') ?>"
                                 data-image-urls="<?= Html::encode(implode("\n", $deletedRecord->imageUrls)) ?>">
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
                                <?= $stackedImages($deletedRecord->imageUrls) ?>
                                <div class="activity-meta">
                                    <i class="bi bi-clock me-1"></i><span class="utc-time" data-utc="<?= Html::encode($deletedRecord->publishedAt ?? '') ?>"></span>
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
                    <input type="hidden" name="publicationTz" id="schedulePublicationTz" value="">
                    
                    <div class="mb-3">
                        <label class="form-label" for="scheduleAt">Дата и время публикации</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-calendar4"></i>
                            </span>
                            <input type="text" id="scheduleAt" name="publicationAt"
                                   class="form-control publication-datepicker-time"
                                   autocomplete="off">
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
$csrfParam = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->csrfToken;
$filterSaveUrl = \yii\helpers\Url::to(['site/forum-filter-save']);
$this->registerJs(
    "var __FILTER_SAVE_URL = '{$filterSaveUrl}';
var __CSRF_PARAM = '{$csrfParam}';
var __CSRF_TOKEN = '{$csrfToken}';
" . <<<'JS'
jQuery(document).ready(function () {
    var pickerFormat = 'DD.MM.YYYY HH:mm';

    function roundUpToNearest10Minutes(m) {
        var minutes = m.minute();
        var remainder = minutes % 10;
        if (remainder === 0 && m.second() === 0 && m.millisecond() === 0) {
            return m.clone().add(10, 'minute').startOf('minute');
        }
        return m.clone().add(10 - remainder, 'minute').startOf('minute');
    }

    function roundMomentTo10(m) {
        return roundUpToNearest10Minutes(m);
    }

    function computeNextPublicationSlot() {
        var now = moment();
        var candidate = roundUpToNearest10Minutes(now);
        if (!candidate.isAfter(now)) {
            candidate = candidate.add(10, 'minute');
        }
        return candidate;
    }

    function setupDateTimePicker(inputJq) {
        if (!inputJq.length) return;
        var currentVal = inputJq.val();
        var startMoment;
        if (currentVal && currentVal !== '' && $.trim(currentVal) !== '') {
            startMoment = moment(currentVal, pickerFormat);
            if (!startMoment || !startMoment.isValid()) {
                startMoment = computeNextPublicationSlot();
            }
        } else {
            startMoment = computeNextPublicationSlot();
        }

        var userTz = getPortalTimezone();
        if (userTz && userTz !== 'UTC') {
            if (startMoment && startMoment.isValid()) {
                startMoment = startMoment.tz(userTz);
            }
        }

        try {
            var existing = inputJq.data('daterangepicker');
            if (existing) {
                existing.remove();
                inputJq.removeData('daterangepicker');
                inputJq.off('.daterangepicker');
            }
        } catch (e) {}
        inputJq.daterangepicker({
            singleDatePicker: true,
            timePicker: true,
            timePicker24Hour: true,
            timePickerIncrement: 10,
            startDate: startMoment,
            endDate: startMoment.clone().add(32, 'hour'),
            locale: {
                format: pickerFormat,
            },
        });
        inputJq.val(startMoment.format(pickerFormat));
    }

    function saveFiltersToSession(withImages, withPosts, imagesCount) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', __FILTER_SAVE_URL, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send(__CSRF_PARAM + '=' + encodeURIComponent(__CSRF_TOKEN)
            + '&withImages=' + (withImages ? 1 : 0)
            + '&withPosts=' + (withPosts ? 1 : 0)
            + '&imagesCount=' + (imagesCount || 0));
    }

    var runOnce = false;
    function initAll() {
        if (runOnce) return;
        runOnce = true;
        var publicationAtJq = jQuery('#publicationAt');
        var scheduleAtJq = jQuery('#scheduleAt');
        setupDateTimePicker(publicationAtJq);
        setupDateTimePicker(scheduleAtJq);

        var applyFiltersDirect = function () {
            var imagesSwitch = document.getElementById('forumFilterWithImages');
            var postsSwitch = document.getElementById('forumFilterWithPosts');
            var imagesCountInput = document.getElementById('forumFilterImagesCount');
            var base = (imagesSwitch || postsSwitch || imagesCountInput).getAttribute('data-filter-url');
            var params = [];
            if (imagesSwitch && imagesSwitch.checked) {
                params.push('withImages=1');
            }
            if (postsSwitch && postsSwitch.checked) {
                params.push('withPosts=1');
            }
            if (imagesCountInput && imagesCountInput.value !== '' && parseInt(imagesCountInput.value, 10) > 0) {
                params.push('imagesCount=' + parseInt(imagesCountInput.value, 10));
            }
            var separator = base.indexOf('?') === -1 ? '?' : '&';
            var newUrl = params.length === 0 ? base : base + separator + params.join('&');

            // Save to session fire-and-forget so subsequent form redirects keep filters
            saveFiltersToSession(
                imagesSwitch && imagesSwitch.checked,
                postsSwitch && postsSwitch.checked,
                imagesCountInput ? parseInt(imagesCountInput.value, 10) : 0
            );

            window.location.href = newUrl;
        };

        ['forumFilterWithImages', 'forumFilterWithPosts'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.addEventListener('change', applyFiltersDirect);
        });

        var source = document.getElementById('publicationTextInput');
        if (!source) {
            return;
        }
        source.style.minHeight = '60px';
        source.style.resize = 'none';
        source.style.overflowY = 'auto';

        function resizePublicationTextInput() {
            if (!source) return;
            var maxHeight = window.innerHeight * 0.8;
            source.style.height = 'auto';
            var sh = source.scrollHeight;
            var newHeight = Math.max(60, Math.min(sh, maxHeight));
            source.style.height = newHeight + 'px';
            // Hide scrollbar when content fits, show when it overflows
            if (sh > maxHeight) {
                source.style.overflowY = 'auto';
            } else {
                source.style.overflowY = 'hidden';
            }
        }

        var preview = document.getElementById('publicationPreview');
        var forumTypeInput = document.getElementById('forumEntityType');
        var forumIdInput = document.getElementById('forumEntityId');
        var imagesInput = document.getElementById('publicationImages');
        var imagesPreview = document.getElementById('publicationImagesPreview');
        var previewImages = document.getElementById('publicationPreviewImages');
        var previewCardImgEl = document.getElementById('previewCardImgEl');
        var publicationAtInput = document.getElementById('publicationAt');
        var previewPublicationAt = document.getElementById('previewPublicationAt');
        var sourceTypeInput = document.getElementById('publicationSource');
        var sourceIdInput = document.getElementById('publicationSourceId');
        var publishedAtInput = document.getElementById('publicationAt');
        var scheduleModal = document.getElementById('scheduleModal');

        function updatePreviewPublicationAt() {
            if (!previewPublicationAt) return;
            var val = publicationAtInput ? publicationAtInput.value : '';
            previewPublicationAt.textContent = val || '';
        }

        var parseImageUrls = function (raw) {
            return (raw || '').split('\n').map(function (line) {
                return line.trim();
            }).filter(function (line) {
                return line !== '';
            });
        };

        var renderImagesPreview = function (container, urls) {
            if (!container) {
                return;
            }
            container.innerHTML = '';
            if (!urls.length) {
                container.classList.add('d-none');
                return;
            }
            urls.slice(0, 10).forEach(function (url) {
                var img = document.createElement('img');
                img.src = url;
                img.alt = 'Изображение публикации';
                container.appendChild(img);
            });
            if (urls.length > 10) {
                var plus = document.createElement('span');
                plus.className = 'plus bg-danger';
                plus.textContent = '+' + (urls.length - 10);
                container.appendChild(plus);
            }
            container.classList.remove('d-none');
        };

        var updateSingleImagePreview = function (urls) {
            if (!previewCardImgEl) {
                return;
            }
            if (urls.length === 1) {
                previewCardImgEl.src = urls[0];
                previewCardImgEl.classList.remove('d-none');
                if (previewImages) {
                    previewImages.classList.add('d-none');
                }
            } else {
                previewCardImgEl.classList.add('d-none');
                previewCardImgEl.src = '';
            }
        };

        var updateImages = function () {
            var urls = parseImageUrls(imagesInput ? imagesInput.value : '');
            renderImagesPreview(imagesPreview, urls);
            renderImagesPreview(previewImages, urls);
            updateSingleImagePreview(urls);
        };

        var update = function () {
            if (preview) {
                preview.textContent = source.value || source.placeholder;
            }
        };
        source.addEventListener('input', function () {
            update();
            resizePublicationTextInput();
        });
        resizePublicationTextInput();
        window.addEventListener('resize', resizePublicationTextInput);

        if (imagesInput) {
            imagesInput.addEventListener('input', updateImages);
        }
        if (publicationAtInput) {
            publicationAtInput.addEventListener('input', updatePreviewPublicationAt);
            publicationAtInput.addEventListener('change', updatePreviewPublicationAt);
            publicationAtJq
                .off('.previewPub')
                .on('apply.daterangepicker.previewPub hide.daterangepicker.previewPub cancel.daterangepicker.previewPub', function () {
                    updatePreviewPublicationAt();
                });
        }
        update();
        updateImages();
        updatePreviewPublicationAt();

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

        // Populate hidden fields (timezone) before form submission
        // Filters are saved to session via AJAX, no need to pass via POST
        function populateHiddenFields() {
            var tz = getPortalTimezone();
            if (tz) {
                var tzField1 = document.getElementById('publicationTz');
                if (tzField1) tzField1.value = tz;
                var tzField2 = document.getElementById('schedulePublicationTz');
                if (tzField2) tzField2.value = tz;
            }
        }

        // Attach to forms
        var newPostForm = document.querySelector('form[action*="publication-create"]');
        if (newPostForm) {
            newPostForm.addEventListener('submit', populateHiddenFields);
        }
        var scheduleFormEl = document.getElementById('scheduleForm');
        if (scheduleFormEl) {
            scheduleFormEl.addEventListener('submit', populateHiddenFields);
        }

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
            if (imagesInput) {
                imagesInput.value = log.getAttribute('data-image-urls') || '';
                updateImages();
            }
            scrollToMiddle(log);

            if (sourceTypeInput && sourceIdInput) {
                sourceTypeInput.value = log.getAttribute('data-source-type') || 'new';
                sourceIdInput.value = log.getAttribute('data-source-id') || '';
            }
            if (publishedAtInput) {
                var newVal = log.getAttribute('data-published-at') || publishedAtInput.value;
                publishedAtInput.value = newVal;
                try {
                    var picker = publicationAtJq.data('daterangepicker');
                    if (picker && newVal) {
                        var m = moment(newVal, pickerFormat);
                        if (m.isValid()) {
                            picker.setStartDate(m);
                            picker.setEndDate(m.clone().add(32, 'hour'));
                        }
                    }
                } catch (e) {}
                updatePreviewPublicationAt();
            }
            if (forumTypeInput && forumIdInput) {
                forumTypeInput.value = '';
                forumIdInput.value = '';
            }
        };

        var resetForm = document.querySelector('form[action*="publication-create"]');
        if (resetForm) {
            resetForm.addEventListener('submit', function () {
                if (sourceTypeInput && sourceIdInput && sourceTypeInput.value === 'new') {
                    sourceIdInput.value = '';
                }
                if (forumTypeInput && forumIdInput && forumTypeInput.value === '') {
                    forumIdInput.value = '';
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

        document.querySelectorAll('.forum-publish-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var text = btn.getAttribute('data-text') || '';
                var title = btn.getAttribute('data-title') || '';
                if (title !== '') {
                    text = title + "\n\n" + text;
                }
                source.value = text;
                source.dispatchEvent(new Event('input'));
                if (imagesInput) {
                    imagesInput.value = btn.getAttribute('data-image-urls') || '';
                    updateImages();
                }
                if (sourceTypeInput && sourceIdInput) {
                    sourceTypeInput.value = 'new';
                    sourceIdInput.value = '';
                }
                if (forumTypeInput && forumIdInput) {
                    forumTypeInput.value = btn.getAttribute('data-forum-type') || '';
                    forumIdInput.value = btn.getAttribute('data-forum-id') || '';
                }
                if (publishedAtInput) {
                    var defVal = btn.getAttribute('data-published-at');
                    if (defVal) {
                        publishedAtInput.value = defVal;
                        try {
                            var picker = publicationAtJq.data('daterangepicker');
                            if (picker) {
                                var m = moment(defVal, pickerFormat);
                                if (m.isValid()) {
                                    picker.setStartDate(m);
                                    picker.setEndDate(m.clone().add(32, 'hour'));
                                }
                            }
                        } catch (e) {}
                    }
                    updatePreviewPublicationAt();
                }
                window.scrollTo({ top: 0, behavior: 'smooth' });
                source.focus();
            });
        });

        if (scheduleModal) {
            scheduleModal.addEventListener('show.bs.modal', function (event) {
                var trigger = event.relatedTarget;
                var recordId = trigger ? trigger.getAttribute('data-draft-id') || '' : '';
                var sourceVal = trigger ? trigger.getAttribute('data-source') || 'draft' : 'draft';
                var recordIdInput = document.getElementById('scheduleDraftId');
                var sourceInput = document.getElementById('scheduleSource');
                if (recordIdInput) {
                    recordIdInput.value = recordId;
                }
                if (sourceInput) {
                    sourceInput.value = sourceVal;
                }
                var scheduleInput = document.getElementById('scheduleAt');
                if (scheduleInput) {
                    var scheduleVal = scheduleInput.value;
                    var startM = scheduleVal ? moment(scheduleVal, pickerFormat) : roundMomentTo10(moment());
                    if (!startM.isValid()) startM = roundMomentTo10(moment());
                    try {
                        var schPicker = scheduleAtJq.data('daterangepicker');
                        if (schPicker) {
                            schPicker.setStartDate(startM);
                            schPicker.setEndDate(startM.clone().add(32, 'hour'));
                            scheduleInput.value = startM.format(pickerFormat);
                        }
                    } catch (e) {}
                }
            });
        }

        var imagesCountSlider = document.getElementById('forumFilterImagesCount');
        var imagesCountValue = document.getElementById('forumFilterImagesCountValue');
        if (imagesCountSlider && imagesCountValue) {
            function updateImagesCountDisplay(value) {
                imagesCountValue.textContent = value == 0 ? '∞' : value;
            }
            updateImagesCountDisplay(imagesCountSlider.value);
            // Apply filters on change (when user releases the slider)
            imagesCountSlider.addEventListener('change', function () {
                updateImagesCountDisplay(this.value);
                applyFiltersDirect();
            });
            // Also save to session on input (while dragging) for responsiveness
            imagesCountSlider.addEventListener('input', function () {
                updateImagesCountDisplay(this.value);
            });
        }
        // Sync current filter state to session so that subsequent form redirects preserve filters
        var imgSwitch = document.getElementById('forumFilterWithImages');
        var postsSwitch = document.getElementById('forumFilterWithPosts');
        var countInput = document.getElementById('forumFilterImagesCount');
        saveFiltersToSession(
            imgSwitch ? imgSwitch.checked : false,
            postsSwitch ? postsSwitch.checked : false,
            countInput ? (parseInt(countInput.value, 10) || 0) : 0
        );

        // Convert UTC timestamps to user's local timezone
        (function convertUtcTimes() {
            var tz = getPortalTimezone();
            if (!tz) return;
            var formatter = new Intl.DateTimeFormat('ru-RU', {
                year: 'numeric', month: '2-digit', day: '2-digit',
                hour: '2-digit', minute: '2-digit',
                timeZone: tz
            });
            document.querySelectorAll('.utc-time[data-utc]').forEach(function(el) {
                var utcStr = el.getAttribute('data-utc');
                if (!utcStr) return;
                var d = new Date(utcStr + 'Z');
                if (isNaN(d.getTime())) return;
                el.textContent = formatter.format(d);
            });
        })();
    }
    setTimeout(initAll, 0);
    setTimeout(initAll, 100);
    jQuery(window).on('load', function () { setTimeout(initAll, 0); });
});
JS
);
?>
