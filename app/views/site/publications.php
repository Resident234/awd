<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var \app\shared\Publications\Dto\PublicationData[] $posts */
/** @var \app\shared\Publications\Dto\PublicationData[] $drafts */
/** @var string $now */

use yii\helpers\Html;

$this->title = 'Публикации в канал';

$defaultAt = gmdate('d/m/Y h:i A');

/** @var \app\shared\Publications\Dto\PublicationData[] $duePosts */
$duePosts = array_values(array_filter(
    $posts,
    static fn (\app\shared\Publications\Dto\PublicationData $post): bool => $post->telegramId !== null,
));
?>
<!-- Row start -->
<div class="row">
    <div class="col-xxl-7 col-sm-12 col-12">

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
    <div class="col-xxl-5 col-sm-12 col-12">

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
                            <div class="activity-log" data-text="<?= Html::encode($post->text) ?>">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <p class="mb-0">
                                        <span class="text-primary">#<?= $post->id ?></span>
                                    </p>
                                    <a href="#" class="btn btn-sm btn-outline-primary rounded-pill px-3" title="Редактировать">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="#" class="btn btn-sm btn-outline-danger rounded-pill px-3" title="Удалить">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                    <?php if (!$isPublished): ?>
                                        <a href="#" class="btn btn-sm btn-outline-success rounded-pill px-3" title="Опубликовать">
                                            <i class="bi bi-send"></i>
                                        </a>
                                    <?php endif ?>
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
                            <div class="activity-log" data-text="<?= Html::encode($draft->text) ?>">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <p class="mb-0">
                                        <span class="text-primary">#<?= $draft->id ?></span>
                                    </p>
                                    <a href="#" class="btn btn-sm btn-outline-primary rounded-pill px-3" title="Редактировать">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="#" class="btn btn-sm btn-outline-danger rounded-pill px-3" title="Удалить">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                    <a href="#" class="btn btn-sm btn-outline-success rounded-pill px-3" title="Опубликовать">
                                        <i class="bi bi-send"></i>
                                    </a>
                                    <a href="#" class="btn btn-sm btn-outline-info rounded-pill px-3" title="Запланировать публикацию">
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
</div>
<!-- Row end -->

<?php
$this->registerJs(
    <<<JS
(function () {
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
    };

    document.querySelectorAll('.activity-log').forEach(function (log) {
        log.addEventListener('dblclick', function () {
            setEditing(log);
        });
        log.querySelectorAll('a[title="Редактировать"], a[title="Запланировать публикацию"]').forEach(function (btn) {
            btn.addEventListener('click', function (event) {
                event.preventDefault();
                setEditing(log);
            });
        });
    });
})();
JS
);
?>
