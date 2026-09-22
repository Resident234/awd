<?php

/** @var yii\web\View $this */
/** @var array<int, array{topic: \app\shared\Forum\Dto\TopicData, posts: \app\shared\Forum\Dto\PostData[]}> $topics */

use app\widgets\PublicationsUi;
use yii\helpers\Html;

?>
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
                    <?= PublicationsUi::viewedForm($topic->id, 'topic') ?>
                    <?= PublicationsUi::publishButton($topic->contentText, 'topic', $topic->id, $topic->imageUrls, $topic->title) ?>
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
                                <?= PublicationsUi::viewedForm($post->id, 'post') ?>
                                <?= PublicationsUi::publishButton($post->contentText, 'post', $post->id, $post->imageUrls, $post->title) ?>
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
