<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var \app\shared\Publications\Dto\PublicationData[] $posts */
/** @var \app\shared\Publications\Dto\PublicationData[] $drafts */
/** @var \app\shared\Publications\Dto\PublicationData[] $deleted */
/** @var array<int, array{topic: \app\shared\Forum\Dto\TopicData, posts: \app\shared\Forum\Dto\PostData[]}> $topics */
/** @var bool $withImagesOnly */
/** @var bool $withPostsOnly */
/** @var array{posts: int, drafts: int, deleted: int} $totals */
/** @var int $pageSize */
/** @var array<string, bool> $oldestFirst the order of every switch of the page */
/** @var string $now */

use app\shared\Telegram\Service\ChannelService;
use yii\helpers\Html;

$this->title = 'Публикации в канал';

// One message of the channel carries at most this many characters, so a longer
// text is broken into parts, each in its own field of the form.
$textLimit = ChannelService::TEXT_MAX_LENGTH;

$this->registerCss(
    <<<CSS
.stacked-images.publication-preview-images {
    margin-top: 0.5rem;
}

/* A distributed publication goes to the channel as one message per part: the
   photos first, the text of the part as the caption under them. */
.stacked-images.publication-part-images {
    margin: 0 0 0.5rem;
}

.stacked-images.publication-preview-images img,
.stacked-images.publication-part-images img {
    max-height: 120px;
    width: auto;
    border-radius: 0.375rem;
    object-fit: cover;
}

.stacked-images.publication-preview-images .plus,
.stacked-images.publication-part-images .plus {
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
    align-self: stretch;
    text-align: left;
    width: 100%;
    flex: 0 0 auto;
}

.telegram-preview-text {
    align-self: flex-start;
    width: 100%;
}

.publication-preview-part {
    margin-bottom: 0;
    white-space: pre-wrap;
    word-break: break-word;
    text-align: left;
}

/* The buttons that move a selection sit next to it, in viewport coordinates. */
.publication-selection-actions {
    position: fixed;
    z-index: 1080;
}

.publication-selection-actions .btn {
    white-space: nowrap;
}
CSS
);
?>
<!-- Row start -->
<div class="row">
    <div class="col-12">

        <!-- Forum filters -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title">Фильтры</h5>
            </div>
            <div class="card-body">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="forumFilterWithImages"
                        <?= $withImagesOnly ? 'checked' : '' ?>>
                    <label class="form-check-label" for="forumFilterWithImages">С изображениями</label>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="forumFilterWithPosts"
                        <?= $withPostsOnly ? 'checked' : '' ?>>
                    <label class="form-check-label" for="forumFilterWithPosts">С привязанными постами</label>
                </div>
                <div class="mb-0">
                    <label for="forumFilterImagesCount" class="form-label small">
                        Кол-во изображений
                        <span id="forumFilterImagesCountValue" class="ms-2 fw-bold text-primary"><?= $imagesCount > 0 ? (int)$imagesCount : '∞' ?></span>
                    </label>
                    <input type="range" class="form-range" id="forumFilterImagesCount"
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
                <div class="d-flex align-items-center justify-content-between gap-3">
                    <h5 class="card-title mb-0">Форум</h5>
                    <div class="d-flex align-items-center gap-3">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="pubSortForumTopics"
                                <?= $oldestFirst['forumTopics'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="pubSortForumTopics">Сначала старые топики</label>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="pubSortForumPosts"
                                <?= $oldestFirst['forumPosts'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="pubSortForumPosts">Сначала старые посты</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="scroll350">

                    <!-- Forum topics widget start -->
                    <div class="notification-center h-100">
                        <div class="threads" id="pub-forum-list"><?= $this->render('_block_forum', ['topics' => $topics]) ?></div>
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
                <div class="d-flex flex-column gap-2 w-100" id="publicationPreview"
                     data-source="publicationTextInput"
                     data-placeholder="Введите текст публикации — он отобразится здесь до отправки в канал TRVL."></div>
                <div class="stacked-images publication-preview-images d-none" id="publicationPreviewImages"></div>
            </div>
            <div class="card-footer bg-transparent">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        <i class="bi bi-eye me-1"></i>
                        Текст обновляется по мере ввода
                    </small>
                    <span id="previewPublicationAt" class="badge bg-primary-subtle text-primary rounded-pill px-3"></span>
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3">
                        до <?= $textLimit ?> символов на часть
                    </span>
                </div>
            </div>
        </div>

    </div>
    <div class="col-sm-6 col-6">

        <!-- New post form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title">Новая публикация</h5>
            </div>
            <div class="card-body">
                <form method="post" action="<?= \yii\helpers\Url::to(['site/publication-create']) ?>"
                      data-ajax data-clear-editing>
                    <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>"
                           value="<?= Yii::$app->request->csrfToken ?>">
                    <input type="hidden" name="publicationSource" id="publicationSource" value="new">
                    <input type="hidden" name="publicationSourceId" id="publicationSourceId" value="">
                    <input type="hidden" name="forumEntityType" id="forumEntityType" value="">
                    <input type="hidden" name="forumEntityId" id="forumEntityId" value="">
                    <input type="hidden" name="publicationTz" id="publicationTz" value="">
                    

                    <!-- Textarea: cloned into one field per part once a text goes past the limit -->
                    <div id="publicationTextParts">
                        <div class="mb-3 publication-text-block">
                            <div class="d-flex justify-content-between align-items-baseline">
                                <label for="publicationTextInput" class="form-label mb-0">Текст публикации</label>
                                <small class="text-muted publication-text-count"></small>
                            </div>
                            <textarea class="form-control publication-text-part" id="publicationTextInput"
                                      name="publicationText[]"
                                      placeholder="Введите текст публикации"></textarea>

                            <!-- The row a part is merged with the one under it by;
                                 the last part of the form has none. -->
                            <div class="text-end mt-2 d-none publication-merge-row">
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                        title="Слить эту часть со следующей в одно поле">
                                    <i class="bi bi-arrows-collapse-vertical me-1"></i>Объединить
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Outside the parts box, so the template a new part is cloned from stays clean. -->
                    <div class="d-flex justify-content-end mb-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="publicationSplitPart"
                                title="Разделить часть по курсору, а без курсора — примерно посередине">
                            <i class="bi bi-scissors me-1"></i>Разделить
                        </button>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="publicationNumberParts">
                        <label class="form-check-label" for="publicationNumberParts">
                            <i class="bi bi-list-ol me-1"></i>Нумерация частей
                        </label>
                        <small class="text-muted d-block">
                            Дописывает «Часть 1», «Часть 2» … в начало каждого фрагмента разбитой публикации
                        </small>
                    </div>

                    <!-- Unlike the part numbers this option cannot be folded into the
                         submitted fields, so the checkbox travels to the server itself. -->
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="publicationDistributeImages"
                               name="publicationDistributeImages" value="1">
                        <label class="form-check-label" for="publicationDistributeImages">
                            <i class="bi bi-card-image me-1"></i>Равномерно распределить изображения между частями
                        </label>
                        <small class="text-muted d-block">
                            Раздаёт изображения по частям так, чтобы каждая часть ушла в канал со своей группой
                        </small>
                    </div>

                    <!-- Attached images -->
                    <div class="mb-3">
                        <label for="publicationImages" class="form-label">
                            <i class="bi bi-images me-1"></i>Изображения публикации
                        </label>
                        <textarea class="form-control" id="publicationImages" name="publicationImages"
                                  rows="3"
                                  placeholder="По одному URL изображения в строке&#10;https://example.com/photo1.jpg&#10;https://example.com/photo2.jpg"></textarea>
                        <?php /* Named here are the links a fill left out: the shape is the
                                one the ui-kit gives a day divider inside a chat column. */ ?>
                        <div class="bg-primary-subtle px-3 py-2 m-3 mb-1 rounded-2 text-break d-none"
                             id="publicationImagesNotice" role="status"></div>
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

                <!-- Shown over a selection of text inside one of the parts. The
                     script moves the node to the body, where nothing can shadow
                     the viewport it is placed against. -->
                <div class="publication-selection-actions d-none" id="publicationSelectionActions">
                    <div class="btn-group shadow" role="group" aria-label="Перемещение выделенного текста">
                        <button type="button" class="btn btn-primary btn-sm" id="publicationMovePrevPart"
                                title="Перенести выделенный текст в конец предыдущей части">
                            <i class="bi bi-arrow-left-short me-1"></i>Переместить в предыдущую часть
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" id="publicationMoveNextPart"
                                title="Перенести выделенный текст в начало следующей части">
                            <i class="bi bi-arrow-right-short me-1"></i>Переместить в следующую часть
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        Запись сохраняется в БД и будет отправлена в канал TRVL в заданное время
                    </small>
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3">
                        до <?= $textLimit ?> символов на часть
                    </span>
                </div>
            </div>
        </div>

    </div>

    <div class="col-12">

        <!-- Publications -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between gap-3">
                    <h5 class="card-title mb-0">Публикации</h5>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="pubSortPosts"
                            <?= $oldestFirst['posts'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="pubSortPosts">Сначала старые</label>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="scroll350">

                    <!-- Timeline start -->
                    <div class="m-0" id="pub-posts-list"><?= $this->render('_block_posts', ['posts' => $posts, 'now' => $now]) ?></div>
                    <!-- Timeline end -->

                </div>
            </div>
        </div>

    </div>
    <div class="col-12">

        <!-- Drafts -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between gap-3">
                    <h5 class="card-title mb-0">Черновики</h5>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="pubSortDrafts"
                            <?= $oldestFirst['drafts'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="pubSortDrafts">Сначала старые</label>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="scroll350">

                    <!-- Timeline start -->
                    <div class="m-0" id="pub-drafts-list"><?= $this->render('_block_drafts', ['drafts' => $drafts]) ?></div>
                    <!-- Timeline end -->

                </div>
            </div>
        </div>

    </div>
    <div class="col-12">

        <!-- Deleted -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between gap-3">
                    <h5 class="card-title mb-0">Удаленные</h5>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="pubSortDeleted"
                            <?= $oldestFirst['deleted'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="pubSortDeleted">Сначала старые</label>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="scroll350">

                    <!-- Timeline start -->
                    <div class="m-0" id="pub-deleted-list"><?= $this->render('_block_deleted', ['deleted' => $deleted, 'now' => $now]) ?></div>
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
                      id="scheduleForm" data-ajax>
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
$pageUrl = \yii\helpers\Url::to(['site/publication-page']);
$postPageUrl = \yii\helpers\Url::to(['site/forum-post-page']);
$sortUrl = \yii\helpers\Url::to(['site/publication-sort']);
$blockTotals = json_encode($totals);
$this->registerJs(
    "var __FILTER_SAVE_URL = '{$filterSaveUrl}';
var __PAGE_URL = '{$pageUrl}';
var __POST_PAGE_URL = '{$postPageUrl}';
var __SORT_URL = '{$sortUrl}';
var __PAGE_SIZE = {$pageSize};
var __BLOCK_TOTALS = {$blockTotals};
var __CSRF_PARAM = '{$csrfParam}';
var __CSRF_TOKEN = '{$csrfToken}';
var __TEXT_PART_LIMIT = {$textLimit};
" . <<<'JS'
var __BLOCK_TARGETS = {
    forum: 'pub-forum-list',
    posts: 'pub-posts-list',
    drafts: 'pub-drafts-list',
    deleted: 'pub-deleted-list'
};

jQuery(document).ready(function () {
    var pickerFormat = 'DD.MM.YYYY HH:mm';
    var __FLASH_ID = 'app-flash';

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

    function renderUtcTimes(root) {
        var tz = getPortalTimezone();
        if (!tz) {
            return;
        }
        var formatter = new Intl.DateTimeFormat('ru-RU', {
            year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit',
            timeZone: tz
        });
        (root || document).querySelectorAll('.utc-time[data-utc]').forEach(function (el) {
            var utcStr = el.getAttribute('data-utc');
            if (!utcStr) return;
            var d = new Date(utcStr + 'Z');
            if (isNaN(d.getTime())) return;
            el.textContent = formatter.format(d);
        });
    }

    // A list element carries its publication time as a raw UTC timestamp, while
    // the picker and the server both work with the wall-clock time of the user,
    // so the value has to change timezone before it reaches the form.
    function utcToPickerValue(utcStr) {
        if (!utcStr || typeof moment === 'undefined') {
            return '';
        }
        var utc = moment.utc(utcStr, 'YYYY-MM-DD HH:mm:ss');
        if (!utc.isValid()) {
            return '';
        }
        var local = utc.tz(getPortalTimezone());

        return local.isValid() ? local.format(pickerFormat) : '';
    }

    function showFlash(type, message) {
        var container = document.getElementById(__FLASH_ID);
        if (!container) {
            return;
        }
        var isSuccess = type === 'success';
        var alertBox = document.createElement('div');
        alertBox.className = 'alert ' + (isSuccess ? 'alert-success' : 'alert-danger')
            + ' alert-dismissible fade show d-flex align-items-center';
        alertBox.setAttribute('role', 'alert');
        alertBox.innerHTML = '<i class="bi ' + (isSuccess ? 'bi-check2-circle' : 'bi-x-circle')
            + ' me-2 fs-4 lh-1"></i><div><strong></strong> </div>'
            + '<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>';
        alertBox.querySelector('strong').textContent = isSuccess ? 'Готово:' : 'Ошибка:';
        alertBox.querySelector('div').appendChild(document.createTextNode(message));
        container.innerHTML = '';
        container.appendChild(alertBox);
    }

    function applyBlocks(payload) {
        var blocks = payload.blocks || {};
        Object.keys(__BLOCK_TARGETS).forEach(function (name) {
            if (typeof blocks[name] !== 'string') {
                return;
            }
            var target = document.getElementById(__BLOCK_TARGETS[name]);
            if (!target) {
                return;
            }
            target.innerHTML = blocks[name];
            renderUtcTimes(target);
            resetPaging(name, payload.totals);
        });
        if (typeof payload.flash === 'string') {
            var container = document.getElementById(__FLASH_ID);
            if (container) {
                container.innerHTML = payload.flash;
            }
        }
    }

    function postForJson(url, fields) {
        var body;
        if (typeof FormData === 'function' && fields instanceof FormData) {
            body = fields;
        } else {
            body = new FormData();
            Object.keys(fields || {}).forEach(function (key) {
                body.append(key, fields[key]);
            });
        }
        if (!body.has(__CSRF_PARAM)) {
            body.append(__CSRF_PARAM, __CSRF_TOKEN);
        }

        return fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: body,
            credentials: 'same-origin'
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        }).then(function (payload) {
            if (!payload || typeof payload !== 'object') {
                throw new Error('некорректный ответ сервера');
            }
            return payload;
        });
    }

    // The address bar mirrors the state the server has just stored.
    function mirrorUrl(payload) {
        if (typeof payload.url === 'string' && window.history && window.history.replaceState) {
            window.history.replaceState(null, '', payload.url);
        }
    }

    function postForBlocks(url, fields) {
        return postForJson(url, fields).then(function (payload) {
            applyBlocks(payload);
            mirrorUrl(payload);

            return payload;
        }).catch(function (error) {
            showFlash('error', 'Не удалось обновить списки: '
                + (error && error.message ? error.message : error));
        });
    }

    // Every list the page draws a page at a time: reaching the bottom of a
    // block asks the server for the next __PAGE_SIZE rows of that list. The
    // forum block pages through its topics, while the posts of one topic are
    // paged by the box that holds them.
    var __PAGED_BLOCKS = ['posts', 'drafts', 'deleted', 'forum'];
    // What one row of a paged block looks like: how many of them a block holds
    // is how far the reader already is.
    var __BLOCK_ROWS = {
        posts: '.activity-log',
        drafts: '.activity-log',
        deleted: '.activity-log',
        forum: '.thread'
    };
    // How close to the bottom of a block counts as "the reader ran out of
    // rows"; the request is made early enough to finish before the edge.
    var __SCROLL_EDGE = 80;
    // Every switch that reverses the order of a list, by the name the server
    // knows it under. The forum block has two of them because it reads its
    // topics and its posts in an order of their own.
    var __SORT_SWITCH_IDS = {
        posts: 'pubSortPosts',
        drafts: 'pubSortDrafts',
        deleted: 'pubSortDeleted',
        forumTopics: 'pubSortForumTopics',
        forumPosts: 'pubSortForumPosts'
    };
    var paging = {};

    function pagingState(name) {
        if (!paging[name]) {
            paging[name] = { offset: 0, total: 0, busy: false, failed: false };
        }

        return paging[name];
    }

    function loadedRows(name) {
        var list = document.getElementById(__BLOCK_TARGETS[name]);

        return list ? list.querySelectorAll(__BLOCK_ROWS[name]).length : 0;
    }

    function nearBottom(el) {
        return el.scrollTop + el.clientHeight >= el.scrollHeight - __SCROLL_EDGE;
    }

    // A block that has just been repainted shows its first page again, so the
    // reading position restarts there; the totals of the same response say how
    // much further there is to read.
    function resetPaging(name, totals) {
        if (__PAGED_BLOCKS.indexOf(name) === -1) {
            return;
        }
        var state = pagingState(name);

        if (totals && typeof totals[name] === 'number') {
            state.total = totals[name];
        }
        state.offset = loadedRows(name);
        state.failed = false;
    }

    // Scrolling a block is not enough to reload it: the block of the element
    // that scrolled has to be found, and the element is the viewport
    // OverlayScrollbars keeps, not the list the rows live in.
    function pagedBlockOf(el) {
        if (!el || typeof el.closest !== 'function') {
            return '';
        }
        var scroller = el.closest('.scroll350');
        if (!scroller) {
            return '';
        }
        var found = '';
        __PAGED_BLOCKS.forEach(function (name) {
            var list = document.getElementById(__BLOCK_TARGETS[name]);
            if (list && scroller.contains(list)) {
                found = name;
            }
        });

        return found;
    }

    function loadNextPage(name) {
        var state = pagingState(name);

        if (state.busy || state.failed || state.offset >= state.total) {
            return;
        }
        state.busy = true;

        postForJson(__PAGE_URL, { block: name, offset: state.offset }).then(function (payload) {
            var list = document.getElementById(__BLOCK_TARGETS[name]);

            if (!list || payload.block !== name || typeof payload.html !== 'string') {
                return;
            }
            list.insertAdjacentHTML('beforeend', payload.html);
            renderUtcTimes(list);

            if (typeof payload.offset === 'number') {
                state.offset = payload.offset;
            }
            if (typeof payload.total === 'number') {
                state.total = payload.total;
            }
        }).catch(function (error) {
            // One failure stops the block: the reader is still looking at the
            // same bottom edge, and retrying would fire on every pixel of it.
            state.failed = true;
            showFlash('error', 'Не удалось догрузить список: '
                + (error && error.message ? error.message : error));
        }).then(function () {
            state.busy = false;
        });
    }

    // A discussion keeps its place in its own markup: the box says how many of
    // its posts are on screen and how many the filters leave, so a repainted
    // forum block restarts every one of them at its first page for free.
    function loadNextReplies(box) {
        var topic = parseInt(box.dataset.topic, 10) || 0;
        var offset = parseInt(box.dataset.offset, 10) || 0;
        var total = parseInt(box.dataset.total, 10) || 0;

        if (box.__busy || box.__failed || topic <= 0 || offset >= total) {
            return;
        }
        box.__busy = true;

        postForJson(__POST_PAGE_URL, { topic: topic, offset: offset }).then(function (payload) {
            if (payload.topic !== topic || typeof payload.html !== 'string') {
                return;
            }
            box.insertAdjacentHTML('beforeend', payload.html);

            if (typeof payload.offset === 'number') {
                box.dataset.offset = payload.offset;
            }
            if (typeof payload.total === 'number') {
                box.dataset.total = payload.total;
            }
        }).catch(function (error) {
            box.__failed = true;
            showFlash('error', 'Не удалось догрузить посты: '
                + (error && error.message ? error.message : error));
        }).then(function () {
            box.__busy = false;
        });
    }

    // The answer of a switch comes back as the first page of the new order,
    // so the reader restarts the block at the end of the list they asked for.
    function watchSortSwitches() {
        Object.keys(__SORT_SWITCH_IDS).forEach(function (name) {
            var input = document.getElementById(__SORT_SWITCH_IDS[name]);
            if (!input) {
                return;
            }
            input.addEventListener('change', function () {
                postForJson(__SORT_URL, { block: name, oldest: input.checked ? '1' : '0' }).then(function (payload) {
                    applyBlocks(payload);
                    mirrorUrl(payload);
                }).catch(function (error) {
                    // The list kept the order it was showing, so the switch
                    // has to keep it too.
                    input.checked = !input.checked;
                    showFlash('error', 'Не удалось изменить порядок: '
                        + (error && error.message ? error.message : error));
                });
            });
        });
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

            postForBlocks(__FILTER_SAVE_URL, {
                withImages: imagesSwitch && imagesSwitch.checked ? 1 : 0,
                withPosts: postsSwitch && postsSwitch.checked ? 1 : 0,
                imagesCount: imagesCountInput ? (parseInt(imagesCountInput.value, 10) || 0) : 0
            });
        };

        ['forumFilterWithImages', 'forumFilterWithPosts'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.addEventListener('change', applyFiltersDirect);
        });

        var partsBox = document.getElementById('publicationTextParts');
        var source = document.getElementById('publicationTextInput');
        if (!partsBox || !source) {
            return;
        }
        // The first field is the template the extra parts are cloned from. It is
        // never replaced itself, so `source` stays a valid reference.
        var partTemplate = partsBox.querySelector('.publication-text-block').cloneNode(true);
        var numberPartsInput = document.getElementById('publicationNumberParts');
        var distributeImagesInput = document.getElementById('publicationDistributeImages');
        var splitButton = document.getElementById('publicationSplitPart');

        function textParts() {
            return Array.prototype.slice.call(partsBox.querySelectorAll('.publication-text-part'));
        }

        function isPartField(el) {
            return !!(el && el.classList && el.classList.contains('publication-text-part'));
        }

        function partValues() {
            return textParts().map(function (field) {
                return field.value;
            });
        }

        function isNumbered() {
            return !!(numberPartsInput && numberPartsInput.checked);
        }

        function isDistributingImages() {
            return !!(distributeImagesInput && distributeImagesInput.checked);
        }

        // The «Часть N» prefix travels inside the message, so it eats into the
        // length a text is split at. 16 characters cover a three-digit number.
        function partLimit() {
            return isNumbered() ? __TEXT_PART_LIMIT - 16 : __TEXT_PART_LIMIT;
        }

        function stripPartNumber(text) {
            return text.replace(/^Часть \d+[.:]?\s*(\n|$)/, '');
        }

        // Break at the last paragraph, then line, then word that still fits;
        // a text without any of those near the boundary is cut hard.
        function splitIntoParts(text) {
            var limit = partLimit();
            var parts = [];
            var rest = text;

            while (rest.length > limit) {
                var cut = rest.lastIndexOf('\n\n', limit);
                if (cut < Math.floor(limit / 2)) {
                    cut = rest.lastIndexOf('\n', limit);
                }
                if (cut < Math.floor(limit / 2)) {
                    cut = rest.lastIndexOf(' ', limit);
                }
                if (cut < Math.floor(limit / 2)) {
                    cut = limit;
                }
                parts.push(rest.slice(0, cut).replace(/\s+$/, ''));
                rest = rest.slice(cut).replace(/^\s+/, '');
            }

            if (rest !== '') {
                parts.push(rest);
            }

            return parts.length > 0 ? parts : [''];
        }

        function applyPartNumbers() {
            var fields = textParts();
            if (fields.length < 2) {
                return;
            }

            fields.forEach(function (field, index) {
                var bare = stripPartNumber(field.value);
                field.value = isNumbered() ? 'Часть ' + (index + 1) + '\n\n' + bare : bare;
            });
        }

        function updateCounters() {
            textParts().forEach(function (field) {
                var counter = field.closest('.publication-text-block').querySelector('.publication-text-count');
                if (!counter) {
                    return;
                }
                counter.textContent = field.value.length + ' / ' + __TEXT_PART_LIMIT;
                counter.className = 'publication-text-count'
                    + (field.value.length > __TEXT_PART_LIMIT ? ' text-danger' : ' text-muted');
            });
        }

        // Every part but the last one carries the row that merges it with the
        // neighbour below, so the last row of the form stays hidden.
        function mergeRows() {
            return Array.prototype.slice.call(partsBox.querySelectorAll('.publication-merge-row'));
        }

        function updateMergeRows() {
            var rows = mergeRows();

            rows.forEach(function (row, index) {
                row.classList.toggle('d-none', index === rows.length - 1);
            });
        }

        function setTextParts(values) {
            // The fields are replaced, and with them the selection the popup points at.
            hideSelectionActions();

            Array.prototype.slice
                .call(partsBox.querySelectorAll('.publication-text-block'), 1)
                .forEach(function (block) {
                    block.parentNode.removeChild(block);
                });

            values.forEach(function (value, index) {
                if (index === 0) {
                    source.value = value;
                    return;
                }
                var block = partTemplate.cloneNode(true);
                var field = block.querySelector('.publication-text-part');
                field.id = 'publicationTextInput' + (index + 1);
                block.querySelector('label').setAttribute('for', field.id);
                partsBox.appendChild(block);
                field.value = value;
            });

            textParts().forEach(function (field, index) {
                var label = field.closest('.publication-text-block').querySelector('label');
                label.textContent = values.length > 1 ? 'Часть ' + (index + 1) : 'Текст публикации';
            });

            updateMergeRows();
            applyPartNumbers();
            updateCounters();
            fitTextInputNow();
            update();
        }

        // Splitting runs when a text arrives from outside — a forum post, a
        // record opened for editing — and while the form still holds one field.
        // Parts the user split by hand are left alone unless one of them no
        // longer fits a message.
        function splitIfNeeded() {
            var values = partValues();
            var limit = partLimit();
            var overflowing = values.filter(function (value) {
                return value.length > limit;
            });

            if (overflowing.length === 0) {
                return false;
            }

            var bare = values.map(function (value) {
                return stripPartNumber(value);
            }).join('\n\n');

            setTextParts(splitIntoParts(bare));

            return true;
        }

        function loadText(text) {
            setTextParts(splitIntoParts(text));
        }

        // --- manual split of one part into two --------------------------------

        // A cut is only accepted when it leaves text on both sides, so no snap
        // can produce an empty or a blank part.
        function usableCut(text, cut) {
            return cut > 0 && cut < text.length
                && text.slice(0, cut).trim() !== '' && text.slice(cut).trim() !== '';
        }

        // A sentence ends at . ! ? … followed by whitespace.
        function endsSentence(text, cut) {
            if (cut <= 0 || cut >= text.length) {
                return false;
            }

            return '.!?…'.indexOf(text[cut - 1]) !== -1 && /\s/.test(text[cut]);
        }

        function endsLine(text, cut) {
            return cut > 0 && cut < text.length && text[cut - 1] === '\n';
        }

        function endsParagraph(text, cut) {
            return endsLine(text, cut) && text[cut - 2] === '\n';
        }

        // The first character of a word after whitespace: cutting there keeps the
        // words on both sides whole.
        function endsWord(text, cut) {
            return cut > 0 && cut < text.length && /\s/.test(text[cut - 1]) && !/\s/.test(text[cut]);
        }

        // Closest accepted position to `from`. The side before it is tried first,
        // so an equal distance keeps the bigger piece in the part being split.
        function snapCut(text, from, isCut, range) {
            for (var distance = 0; distance <= range; distance++) {
                var before = from - distance;
                if (isCut(text, before) && usableCut(text, before)) {
                    return before;
                }

                var after = from + distance;
                if (distance > 0 && isCut(text, after) && usableCut(text, after)) {
                    return after;
                }
            }

            return -1;
        }

        // A sentence far away from the caret is not the place the user meant, so
        // the snap only reaches this far and then leaves the caret where it is.
        var MANUAL_SNAP_RANGE = 600;

        function manualCut(text, caret) {
            if (caret <= 0 || caret >= text.length) {
                return -1;
            }

            var sentence = snapCut(text, caret, endsSentence, MANUAL_SNAP_RANGE);
            if (sentence !== -1) {
                return sentence;
            }

            var word = snapCut(text, caret, endsWord, MANUAL_SNAP_RANGE);
            if (word !== -1) {
                return word;
            }

            return usableCut(text, caret) ? caret : -1;
        }

        // Without a caret the text is halved at the closest break: a blank line,
        // then the end of a line, then a word edge.
        function middleCut(text) {
            var half = Math.floor(text.length / 2);
            var breaks = [endsParagraph, endsLine, endsWord];

            for (var index = 0; index < breaks.length; index++) {
                var cut = snapCut(text, half, breaks[index], text.length);
                if (cut !== -1) {
                    return cut;
                }
            }

            return usableCut(text, half) ? half : -1;
        }

        // The field the caret was in last: a click on the button pulls the focus
        // out of the textarea before the handler runs, so activeElement is the
        // button by then and the remembered field is what carries the caret.
        var lastEditedField = null;

        function splitTarget(fields, values) {
            var index = fields.indexOf(lastEditedField);
            if (index !== -1 && values[index].trim() !== '') {
                return index;
            }

            // Nothing focused to go by: the longest part is the one worth halving.
            var longest = -1;
            values.forEach(function (value, position) {
                if (value.trim() === '') {
                    return;
                }

                if (longest === -1 || value.length > values[longest].length) {
                    longest = position;
                }
            });

            return longest;
        }

        function splitPartAtCaret() {
            var fields = textParts();
            var values = partValues().map(stripPartNumber);
            var index = splitTarget(fields, values);

            if (index === -1) {
                return;
            }

            var text = values[index];
            // The «Часть N» prefix travels in the shown value, so the caret offset
            // has to be moved out of it before it points into the text.
            var caret = (fields[index].selectionStart || 0) - (fields[index].value.length - text.length);
            var cut = manualCut(text, caret);
            if (cut === -1) {
                cut = middleCut(text);
            }

            if (cut === -1) {
                return;
            }

            setTextParts(values.slice(0, index)
                .concat([text.slice(0, cut).replace(/\s+$/, ''), text.slice(cut).replace(/^\s+/, '')])
                .concat(values.slice(index + 1)));

            var next = textParts()[index + 1];
            if (next && typeof next.focus === 'function') {
                next.focus();
                if (typeof next.setSelectionRange === 'function') {
                    next.setSelectionRange(0, 0);
                }
            }
        }

        // Two neighbouring parts into one: the seam becomes a paragraph, exactly
        // the way the automatic split and the moved selections join text.
        function mergeParts(index) {
            var values = partValues().map(stripPartNumber);

            if (index < 0 || index + 1 >= values.length) {
                return;
            }

            var seam = values[index].replace(/\s+$/, '').length;
            values[index] = joinParts(values[index], values[index + 1]);
            values.splice(index + 1, 1);
            setTextParts(values);

            // The joined text does not have to fit one message, so it goes back
            // through the split when it stopped fitting.
            if (splitIfNeeded()) {
                return;
            }

            var field = textParts()[index];
            if (field) {
                // The caret marks the place the two parts grew together at.
                field.focus();
                field.setSelectionRange(seam, seam);
            }
        }

        // --- moving a selection of text between two parts ----------------------

        // The seam of a join is a paragraph: both sides keep their own text.
        function joinParts(first, second) {
            var head = first.replace(/\s+$/, '');
            var tail = second.replace(/^\s+/, '');

            if (head === '') {
                return tail;
            }

            return tail === '' ? head : head + '\n\n' + tail;
        }

        // Forward puts the selection in front of the next part, backward — after
        // the previous one, so the reading order survives. A part the move empties
        // is dropped, and at the edge of the form the text gets a part of its own.
        function shiftSelectedText(values, index, from, to, forward) {
            var text = values[index];
            var raw = text.slice(from, to);
            var moved = raw.replace(/^\s+/, '').replace(/\s+$/, '');

            if (moved === '') {
                return null;
            }

            // Whitespace the cut carried away comes back as a single separator, so
            // the words on both sides of it do not grow together.
            var before = text.slice(0, from);
            var after = text.slice(to);
            var head = before.replace(/\s+$/, '');
            var tail = after.replace(/^\s+/, '');
            var wholeWords = head === before && tail === after;
            var source = wholeWords || head === '' || tail === ''
                ? head + tail
                : head + ' ' + tail;
            var target = forward ? index + 1 : index - 1;
            var next = values.slice();
            next[index] = source;

            if (target < 0) {
                next.unshift(moved);
                target = 0;
            } else if (target >= next.length) {
                next.push(moved);
            } else {
                next[target] = forward
                    ? joinParts(moved, next[target])
                    : joinParts(next[target], moved);
            }

            var caret = forward ? moved.length : next[target].length;

            if (source === '') {
                next.splice(index, 1);
                if (index < target) {
                    target -= 1;
                }
            }

            return { values: next, index: target, caret: caret };
        }

        var selectionPopup = document.getElementById('publicationSelectionActions');
        var movePrevPartButton = document.getElementById('publicationMovePrevPart');
        var moveNextPartButton = document.getElementById('publicationMoveNextPart');
        // The field the popup was shown over: its selection is what moves.
        var selectionField = null;

        function hideSelectionActions() {
            selectionField = null;

            if (selectionPopup) {
                selectionPopup.classList.add('d-none');
            }
        }

        // Selection offsets of a field in the text without the «Часть N» prefix,
        // which is what the parts are stored as.
        function selectionRange(field) {
            var text = stripPartNumber(field.value);
            var prefix = field.value.length - text.length;

            return {
                text: text,
                from: Math.max(0, (field.selectionStart || 0) - prefix),
                to: Math.max(0, Math.min(text.length, (field.selectionEnd || 0) - prefix)),
            };
        }

        // The popup goes above the end of the selection, below it when the top of
        // the viewport is in the way, and inside the viewport on both axes.
        function popupPoint(caret, size, viewport) {
            var margin = 8;
            var left = Math.max(margin, Math.min(
                caret.x - size.width / 2,
                viewport.width - size.width - margin
            ));
            var top = caret.y - size.height - margin;

            if (top < margin) {
                top = caret.y + margin;
            }

            if (top + size.height > viewport.height - margin) {
                top = Math.max(margin, viewport.height - size.height - margin);
            }

            return { left: Math.round(left), top: Math.round(top) };
        }

        // A textarea gives no pixel coordinates for a position in its text, so the
        // offset is measured in a hidden copy of the field up to that position.
        var MIRROR_STYLE_PROPS = [
            'fontFamily', 'fontSize', 'fontWeight', 'fontStyle', 'fontVariant',
            'letterSpacing', 'lineHeight', 'wordSpacing', 'textTransform', 'textAlign',
            'textIndent', 'width', 'paddingTop', 'paddingRight', 'paddingBottom',
            'paddingLeft', 'borderTopWidth', 'borderRightWidth', 'borderBottomWidth',
            'borderLeftWidth', 'boxSizing',
        ];

        function caretPoint(field, position) {
            var computed = window.getComputedStyle(field);
            var mirror = document.createElement('div');

            MIRROR_STYLE_PROPS.forEach(function (prop) {
                mirror.style[prop] = computed[prop];
            });
            // The copied widths only take part in the box model with a style.
            mirror.style.borderStyle = 'solid';
            mirror.style.borderColor = 'transparent';
            mirror.style.position = 'absolute';
            mirror.style.visibility = 'hidden';
            mirror.style.whiteSpace = 'pre-wrap';
            mirror.style.overflowWrap = 'break-word';
            mirror.style.top = '0';
            mirror.style.left = '-9999px';

            // A scrollbar eats into the text of the field but not into its box, and
            // the copy would wrap a scrollbar later than the original.
            var gutter = field.offsetWidth - field.clientWidth;
            if (gutter > 0) {
                mirror.style.width = (parseFloat(computed.width) - gutter) + 'px';
            }

            mirror.textContent = field.value.slice(0, position);

            var marker = document.createElement('span');
            marker.textContent = '\u200b';
            mirror.appendChild(marker);

            document.body.appendChild(mirror);
            var fieldRect = field.getBoundingClientRect();
            var mirrorRect = mirror.getBoundingClientRect();
            var markerRect = marker.getBoundingClientRect();
            document.body.removeChild(mirror);

            // A zero width marker is only as tall as the glyph box, so the line of
            // the text itself is what the popup has to clear.
            var lineHeight = parseFloat(computed.lineHeight);

            return {
                x: fieldRect.left + markerRect.left - mirrorRect.left,
                y: fieldRect.top + markerRect.top - mirrorRect.top
                    + (isNaN(lineHeight) ? markerRect.height : lineHeight)
                    - field.scrollTop,
            };
        }

        function showSelectionActions(field) {
            var selection = selectionRange(field);

            if (!selectionPopup || textParts().length < 2
                || selection.text.slice(selection.from, selection.to).trim() === '') {
                hideSelectionActions();

                return;
            }

            // Measured while shown: a hidden popup has no size to place by. Both
            // happen in one task, so the old position never paints.
            selectionPopup.classList.remove('d-none');
            selectionField = field;

            var point = popupPoint(
                caretPoint(field, selection.to),
                { width: selectionPopup.offsetWidth, height: selectionPopup.offsetHeight },
                { width: window.innerWidth, height: window.innerHeight }
            );
            selectionPopup.style.left = point.left + 'px';
            selectionPopup.style.top = point.top + 'px';
        }

        function moveSelectedText(forward) {
            var index = textParts().indexOf(selectionField);

            if (index === -1) {
                hideSelectionActions();

                return;
            }

            var selection = selectionRange(selectionField);
            var moved = shiftSelectedText(
                partValues().map(stripPartNumber),
                index,
                selection.from,
                selection.to,
                forward
            );
            hideSelectionActions();

            if (!moved) {
                return;
            }

            setTextParts(moved.values);

            var target = textParts()[moved.index];
            if (target && typeof target.focus === 'function') {
                target.focus();

                if (typeof target.setSelectionRange === 'function') {
                    target.setSelectionRange(moved.caret, moved.caret);
                }
            }
        }

        function resizePublicationTextInput() {
            var maxHeight = window.innerHeight * 0.8;
            textParts().forEach(function (field) {
                field.style.minHeight = '60px';
                field.style.resize = 'none';
                field.style.overflowY = 'auto';
                field.style.height = 'auto';
                var sh = field.scrollHeight;
                field.style.height = Math.max(60, Math.min(sh, maxHeight)) + 'px';
                // Hide scrollbar when content fits, show when it overflows
                field.style.overflowY = sh > maxHeight ? 'auto' : 'hidden';
            });
        }

        // The fit measures with height:auto, which drops the box to its two
        // default rows for one layout pass and moves the caret with it, so it
        // runs only once typing stops instead of on every keystroke.
        var resizeTimer = null;

        function fitTextInputNow() {
            if (resizeTimer) {
                clearTimeout(resizeTimer);
                resizeTimer = null;
            }
            resizePublicationTextInput();
        }

        function fitTextInputAfterTyping() {
            if (resizeTimer) {
                clearTimeout(resizeTimer);
            }
            resizeTimer = setTimeout(fitTextInputNow, 5000);
        }

        var preview = document.getElementById('publicationPreview');
        var forumTypeInput = document.getElementById('forumEntityType');
        var forumIdInput = document.getElementById('forumEntityId');
        var imagesInput = document.getElementById('publicationImages');
        var imagesNotice = document.getElementById('publicationImagesNotice');
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

        // The split the service performs on save, mirrored here so the card shows
        // every part with the album it will be sent to the channel with.
        var groupImages = function (urls, partCount) {
            var groups = [];
            var base = Math.floor(urls.length / partCount);
            var extra = urls.length % partCount;
            var offset = 0;

            for (var index = 0; index < partCount; index++) {
                var size = base + (index < extra ? 1 : 0);
                groups.push(urls.slice(offset, offset + size));
                offset += size;
            }

            return groups;
        };

        var updateImages = function () {
            renderImagesPreview(imagesPreview, parseImageUrls(imagesInput ? imagesInput.value : ''));
            update();
        };

        // The forum keeps the image links it once read, and a link can outlive
        // the file behind it. The only way to notice before the album goes to
        // the channel is to ask for every one of them.
        var __IMAGE_PROBE_TIMEOUT = 6000;
        var imageProbeRun = 0;

        var probeImage = function (url) {
            return new Promise(function (resolve) {
                var settled = false;
                var timer = 0;
                var img = new Image();

                function finish(alive) {
                    if (settled) {
                        return;
                    }
                    settled = true;
                    clearTimeout(timer);
                    img.onload = null;
                    img.onerror = null;
                    resolve(alive);
                }
                timer = setTimeout(function () {
                    // A link that never answered is not proven dead: it stays.
                    finish(true);
                }, __IMAGE_PROBE_TIMEOUT);
                img.onload = function () { finish(true); };
                img.onerror = function () { finish(false); };
                img.src = url;
            });
        };

        var renderImagesNotice = function (deadUrls) {
            if (!imagesNotice) {
                return;
            }
            if (deadUrls.length === 0) {
                imagesNotice.classList.add('d-none');

                return;
            }
            imagesNotice.textContent = 'Мёртвые ссылки в изображения не добавлены ('
                + deadUrls.length + '): ' + deadUrls.join(', ');
            imagesNotice.classList.remove('d-none');
        };

        // The whole album goes into the field at once, so a form submitted while
        // the links are still being probed cannot lose a live image; the field
        // narrows to the ones that answered as soon as all of them have.
        var setFormImages = function (raw) {
            if (!imagesInput) {
                return;
            }
            var urls = parseImageUrls(raw);
            var run = ++imageProbeRun;

            imagesInput.value = urls.join('\n');
            renderImagesNotice([]);
            updateImages();
            if (urls.length === 0) {
                return;
            }
            Promise.all(urls.map(probeImage)).then(function (answered) {
                var untouched = parseImageUrls(imagesInput.value).join('\n') === urls.join('\n');

                if (run !== imageProbeRun || !untouched) {
                    return;
                }
                var dead = urls.filter(function (url, index) { return !answered[index]; });

                imagesInput.value = urls.filter(function (url, index) { return answered[index]; }).join('\n');
                renderImagesNotice(dead);
                updateImages();
            });
        };

        var update = function () {
            if (!preview) {
                return;
            }

            var values = partValues().filter(function (value) {
                return value !== '';
            });
            var placeholder = preview.getAttribute('data-placeholder') || '';
            var texts = values.length > 0 ? values : [placeholder];
            var urls = parseImageUrls(imagesInput ? imagesInput.value : '');
            var spread = isDistributingImages() && texts.length > 1 && urls.length > 0;
            var groups = spread ? groupImages(urls, texts.length) : [];

            preview.textContent = '';
            texts.forEach(function (text, index) {
                var part = document.createElement('div');
                part.className = 'event-content bg-light-subtle rounded-3 p-3 flex-grow-1 telegram-preview-text';
                if (spread && groups[index].length > 0) {
                    var box = document.createElement('div');
                    box.className = 'stacked-images publication-part-images';
                    part.appendChild(box);
                    renderImagesPreview(box, groups[index]);
                }
                var body = document.createElement('p');
                body.className = 'publication-preview-part';
                body.textContent = text;
                part.appendChild(body);
                preview.appendChild(part);
            });

            if (spread) {
                // The URLs live inside their parts now, so the shared strip under
                // the text would show the same images a second time.
                renderImagesPreview(previewImages, []);
                updateSingleImagePreview([]);
            } else {
                renderImagesPreview(previewImages, urls);
                updateSingleImagePreview(urls);
            }
        };
        // One listener for every part field, including the ones cloned later.
        partsBox.addEventListener('input', function (event) {
            if (!isPartField(event.target)) {
                return;
            }
            // Typing replaces the selection the popup was pointing at.
            hideSelectionActions();
            updateCounters();
            update();
            if (textParts().length === 1) {
                splitIfNeeded();
            }
            fitTextInputAfterTyping();
        });
        // Remembers which part holds the caret; see `lastEditedField`.
        partsBox.addEventListener('focusin', function (event) {
            if (isPartField(event.target)) {
                lastEditedField = event.target;
            }
        });
        // A selection is made with the mouse or with shift and the arrows, and the
        // fields never report it as an event of their own.
        ['mouseup', 'keyup'].forEach(function (name) {
            partsBox.addEventListener(name, function (event) {
                if (isPartField(event.target)) {
                    showSelectionActions(event.target);
                }
            });
        });
        partsBox.addEventListener('focusout', hideSelectionActions);
        if (numberPartsInput) {
            numberPartsInput.addEventListener('change', function () {
                splitIfNeeded();
                applyPartNumbers();
                updateCounters();
                update();
            });
        }
        if (distributeImagesInput) {
            distributeImagesInput.addEventListener('change', updateImages);
        }
        if (splitButton) {
            splitButton.addEventListener('click', splitPartAtCaret);
        }
        // One listener for the rows of every part, including the cloned ones.
        partsBox.addEventListener('click', function (event) {
            var row = event.target.closest('.publication-merge-row');

            if (row) {
                mergeParts(mergeRows().indexOf(row));
            }
        });
        if (selectionPopup) {
            // The popup is placed against the viewport, which a transformed
            // ancestor of the form would quietly replace with itself.
            document.body.appendChild(selectionPopup);
            // The buttons must keep the focus in the field the text is selected in.
            selectionPopup.addEventListener('mousedown', function (event) {
                event.preventDefault();
            });
            movePrevPartButton.addEventListener('click', function () {
                moveSelectedText(false);
            });
            moveNextPartButton.addEventListener('click', function () {
                moveSelectedText(true);
            });
        }
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                hideSelectionActions();
            }
        });
        // Viewport coordinates: the popup does not follow what moves under it.
        window.addEventListener('resize', hideSelectionActions);
        window.addEventListener('scroll', hideSelectionActions, true);
        resizePublicationTextInput();
        window.addEventListener('resize', fitTextInputNow);

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
        updateCounters();
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
            if (editingLog && editingLog.isConnected) {
                editingLog.querySelector('.editing-badge').classList.add('d-none');
            }
            editingLog = log;
            log.querySelector('.editing-badge').classList.remove('d-none');
            loadText(log.getAttribute('data-text') || '');
            setFormImages(log.getAttribute('data-image-urls') || '');
            scrollToMiddle(log);

            if (sourceTypeInput && sourceIdInput) {
                sourceTypeInput.value = log.getAttribute('data-source-type') || 'new';
                sourceIdInput.value = log.getAttribute('data-source-id') || '';
            }
            if (publishedAtInput) {
                var newVal = utcToPickerValue(log.getAttribute('data-published-at-utc'))
                    || publishedAtInput.value;
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
                // A part the user made too long by hand is broken again right
                // before the fields are read for the request.
                splitIfNeeded();
                applyPartNumbers();
                if (sourceTypeInput && sourceIdInput && sourceTypeInput.value === 'new') {
                    sourceIdInput.value = '';
                }
                if (forumTypeInput && forumIdInput && forumTypeInput.value === '') {
                    forumIdInput.value = '';
                }
            });
        }

        var hideScheduleModal = function () {
            if (!scheduleModal || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
                return;
            }
            try {
                bootstrap.Modal.getOrCreateInstance(scheduleModal).hide();
            } catch (e) {}
        };

        // Back to the blank "new record" state the page reload used to leave behind,
        // otherwise the next submit would overwrite the record just saved.
        var clearEditingState = function () {
            if (editingLog && editingLog.isConnected) {
                editingLog.querySelector('.editing-badge').classList.add('d-none');
            }
            editingLog = null;
            setTextParts(['']);
            if (numberPartsInput) {
                numberPartsInput.checked = false;
            }
            if (distributeImagesInput) {
                distributeImagesInput.checked = false;
            }
            setFormImages('');
            if (sourceTypeInput) sourceTypeInput.value = 'new';
            if (sourceIdInput) sourceIdInput.value = '';
            if (forumTypeInput) forumTypeInput.value = '';
            if (forumIdInput) forumIdInput.value = '';
            if (publishedAtInput) {
                var next = computeNextPublicationSlot().format(pickerFormat);
                publishedAtInput.value = next;
                try {
                    var picker = publicationAtJq.data('daterangepicker');
                    if (picker) {
                        var m = moment(next, pickerFormat);
                        picker.setStartDate(m);
                        picker.setEndDate(m.clone().add(32, 'hour'));
                    }
                } catch (e) {}
                updatePreviewPublicationAt();
            }
        };

        var ajaxInFlight = false;

        // Publications forms post over AJAX and the lists are swapped in place.
        // Without JavaScript the controller keeps answering with a redirect.
        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!form || form.tagName !== 'FORM' || !form.hasAttribute('data-ajax')) {
                return;
            }
            if (ajaxInFlight) {
                event.preventDefault();
                return;
            }
            if (typeof FormData !== 'function') {
                return;
            }
            event.preventDefault();
            ajaxInFlight = true;
            postForBlocks(form.getAttribute('action'), new FormData(form, event.submitter))
                .then(function (payload) {
                    if (!payload || !payload.ok) {
                        return;
                    }
                    if (form.hasAttribute('data-clear-editing')) {
                        clearEditingState();
                    }
                    if (form.id === 'scheduleForm') {
                        hideScheduleModal();
                    }
                })
                .then(function () {
                    ajaxInFlight = false;
                });
        });

        // The lists are swapped in place after every action, so all of their
        // controls are wired through delegated listeners on the document.
        document.addEventListener('dblclick', function (event) {
            var log = event.target.closest ? event.target.closest('.activity-log') : null;
            if (log) {
                setEditing(log);
            }
        });

        document.addEventListener('click', function (event) {
            var editLink = event.target.closest ? event.target.closest('a[title="Редактировать"]') : null;
            if (editLink) {
                var log = editLink.closest('.activity-log');
                if (log) {
                    event.preventDefault();
                    setEditing(log);
                }
                return;
            }
            var forumBtn = event.target.closest ? event.target.closest('.forum-publish-btn') : null;
            if (forumBtn) {
                fillFormFromForum(forumBtn);
            }
        });

        var fillFormFromForum = function (btn) {
            var text = btn.getAttribute('data-text') || '';
            var title = btn.getAttribute('data-title') || '';
            if (title !== '') {
                text = title + "\n\n" + text;
            }
            loadText(text);
            setFormImages(btn.getAttribute('data-image-urls') || '');
            if (sourceTypeInput && sourceIdInput) {
                sourceTypeInput.value = 'new';
                sourceIdInput.value = '';
            }
            if (forumTypeInput && forumIdInput) {
                forumTypeInput.value = btn.getAttribute('data-forum-type') || '';
                forumIdInput.value = btn.getAttribute('data-forum-id') || '';
            }
            if (editingLog && editingLog.isConnected) {
                editingLog.querySelector('.editing-badge').classList.add('d-none');
            }
            editingLog = null;
            source.focus();
        };

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
            // The label follows the thumb while dragging; the lists refresh on release
            imagesCountSlider.addEventListener('input', function () {
                updateImagesCountDisplay(this.value);
            });
        }

        var clearFiltersBtn = document.getElementById('forumFilterClearBtn');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', function (event) {
                event.preventDefault();
                var imagesSwitch = document.getElementById('forumFilterWithImages');
                var postsSwitch = document.getElementById('forumFilterWithPosts');
                if (imagesSwitch) imagesSwitch.checked = false;
                if (postsSwitch) postsSwitch.checked = false;
                if (imagesCountSlider) imagesCountSlider.value = 0;
                if (imagesCountValue) imagesCountValue.textContent = '∞';
                postForBlocks(clearFiltersBtn.getAttribute('href'), {});
            });
        }

        // The first page is already in the markup; the totals say whether a
        // second one is worth reading.
        __PAGED_BLOCKS.forEach(function (name) {
            resetPaging(name, __BLOCK_TOTALS);
        });

        // Switching the order is the server's call: it has to decide which end
        // of the list both the redrawn block and its later pages are read
        // from.
        watchSortSwitches();

        // Scroll does not bubble, so one capture listener on the document sees
        // the viewport of every block, whenever OverlayScrollbars rebuilt it.
        document.addEventListener('scroll', function (event) {
            var scroller = event.target;
            var replies = typeof scroller.closest === 'function' ? scroller.closest('.thread-replies') : null;

            // A discussion box sits inside the list of topics, so its scroll has
            // to be answered first: otherwise it would read as the bottom of the
            // list and ask for another topic.
            if (replies) {
                if (nearBottom(replies)) {
                    loadNextReplies(replies);
                }

                return;
            }
            var name = pagedBlockOf(scroller);

            if (name === '' || !nearBottom(scroller)) {
                return;
            }
            loadNextPage(name);
        }, true);

        renderUtcTimes(document);
    }
    setTimeout(initAll, 0);
    setTimeout(initAll, 100);
    jQuery(window).on('load', function () { setTimeout(initAll, 0); });
});
JS
);
?>
