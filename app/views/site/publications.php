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

use app\shared\Telegram\Service\ChannelService;
use yii\helpers\Html;

$this->title = 'Публикации в канал';

// One message of the channel carries at most this many characters, so a longer
// text is broken into parts, each in its own field of the form.
$textLimit = ChannelService::TEXT_MAX_LENGTH;

$this->registerCss(
    <<<CSS
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
                <h5 class="card-title">Форум</h5>
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
                <div class="stacked-images mt-2 d-none" id="publicationPreviewImages"></div>
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
                        </div>
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
                <h5 class="card-title">Публикации</h5>
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
                <h5 class="card-title">Черновики</h5>
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
                <h5 class="card-title">Удаленные</h5>
            </div>
            <div class="card-body">
                <div class="scroll350">

                    <!-- Timeline start -->
                    <div class="m-0" id="pub-deleted-list"><?= $this->render('_block_deleted', ['deleted' => $deleted]) ?></div>
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
$this->registerJs(
    "var __FILTER_SAVE_URL = '{$filterSaveUrl}';
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
        });
        if (typeof payload.flash === 'string') {
            var container = document.getElementById(__FLASH_ID);
            if (container) {
                container.innerHTML = payload.flash;
            }
        }
    }

    function postForBlocks(url, fields) {
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
            applyBlocks(payload);
            // The address bar mirrors the state the server has just stored.
            if (typeof payload.url === 'string' && window.history && window.history.replaceState) {
                window.history.replaceState(null, '', payload.url);
            }
            return payload;
        }).catch(function (error) {
            showFlash('error', 'Не удалось обновить списки: '
                + (error && error.message ? error.message : error));
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

        function textParts() {
            return Array.prototype.slice.call(partsBox.querySelectorAll('.publication-text-part'));
        }

        function partValues() {
            return textParts().map(function (field) {
                return field.value;
            });
        }

        function isNumbered() {
            return !!(numberPartsInput && numberPartsInput.checked);
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

        function setTextParts(values) {
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
            if (!preview) {
                return;
            }

            var values = partValues().filter(function (value) {
                return value !== '';
            });
            var placeholder = preview.getAttribute('data-placeholder') || '';
            var texts = values.length > 0 ? values : [placeholder];

            preview.textContent = '';
            texts.forEach(function (text) {
                var part = document.createElement('div');
                part.className = 'event-content bg-light-subtle rounded-3 p-3 flex-grow-1 telegram-preview-text';
                var body = document.createElement('p');
                body.className = 'publication-preview-part';
                body.textContent = text;
                part.appendChild(body);
                preview.appendChild(part);
            });
        };
        // One listener for every part field, including the ones cloned later.
        partsBox.addEventListener('input', function (event) {
            var field = event.target;
            if (!field.classList || !field.classList.contains('publication-text-part')) {
                return;
            }
            updateCounters();
            update();
            if (textParts().length === 1) {
                splitIfNeeded();
            }
            fitTextInputAfterTyping();
        });
        if (numberPartsInput) {
            numberPartsInput.addEventListener('change', function () {
                splitIfNeeded();
                applyPartNumbers();
                updateCounters();
                update();
            });
        }
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
            if (imagesInput) {
                imagesInput.value = '';
                updateImages();
            }
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

        renderUtcTimes(document);
    }
    setTimeout(initAll, 0);
    setTimeout(initAll, 100);
    jQuery(window).on('load', function () { setTimeout(initAll, 0); });
});
JS
);
?>
