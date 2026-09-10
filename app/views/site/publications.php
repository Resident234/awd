<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use yii\helpers\Html;

$this->title = 'Публикации в канал';

$publicationPreview = 'TRVL — канал о путешествиях и приключениях. Маршруты, лайфхаки и вдохновение для ваших странствий.';
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
                    <?= Html::encode($publicationPreview) ?>
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
                                   class="form-control datepicker-time">
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i>Опубликовать
                        </button>
                        <button type="submit" class="btn btn-outline-secondary">
                            <i class="bi bi-save me-1"></i>Сохранить
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer bg-transparent">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        Публикация будет отправлена в канал TRVL
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
                        <div class="activity-log" data-text="10 маршрутов по Грузии: от Тбилиси до Сванетии. Проверенные дороги, горные перевалы, гостевые дома и бюджет на каждую поездку. Рассказываем, где остановиться и что обязательно попробовать в пути.">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <p class="mb-0">
                                    <span class="text-primary">#4218</span>
                                </p>
                                <a href="#" class="btn btn-sm btn-outline-primary rounded-pill px-3" title="Редактировать">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <a href="#" class="btn btn-sm btn-outline-danger rounded-pill px-3" title="Удалить">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                            <p class="mb-1">10 маршрутов по Грузии</p>
                            <div class="activity-meta">
                                <i class="bi bi-clock me-1"></i>Сегодня, 18:00
                            </div>
                            <span class="badge bg-success mt-2">Опубликовано</span>
                            <span class="badge bg-warning text-dark mt-2 d-none editing-badge">Редактируется</span>
                        </div>

                        <div class="activity-log" data-text="Как собрать рюкзак в поход: чек-лист снаряжения для похода выходного дня и многодневного маршрута. Вес, одежда, посуда, аптечка и лайфхаки укладки.">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <p class="mb-0">
                                    <span class="text-primary">#4219</span>
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
                            </div>
                            <p class="mb-1">Как собрать рюкзак в поход</p>
                            <div class="stacked-images sm mt-2">
                                <img src="<?= Yii::getAlias('@web/ui-kit/assets/images/user.png') ?>" alt="Attachment">
                                <img src="<?= Yii::getAlias('@web/ui-kit/assets/images/user2.png') ?>" alt="Attachment">
                            </div>
                            <div class="activity-meta mt-2">
                                <i class="bi bi-clock me-1"></i>Сегодня, 21:30
                            </div>
                            <span class="badge bg-info mt-2">Запланировано</span>
                            <span class="badge bg-warning text-dark mt-2 d-none editing-badge">Редактируется</span>
                        </div>

                        <div class="activity-log" data-text="Ночной Стамбул: маршрут выходного дня по вечернему городу — Босфор, Галата, балык-экмек и вид на пролив в огнях. Куда идти после заката и что успеть за 48 часов.">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <p class="mb-0">
                                    <span class="text-primary">#4220</span>
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
                            </div>
                            <p class="mb-1">Ночной Стамбул: маршрут выходного дня</p>
                            <div class="stacked-images sm mt-2">
                                <img src="<?= Yii::getAlias('@web/ui-kit/assets/images/user3.png') ?>" alt="Attachment">
                            </div>
                            <div class="activity-meta mt-2">
                                <i class="bi bi-clock me-1"></i>Завтра, 09:00
                            </div>
                            <span class="badge bg-info mt-2">Запланировано</span>
                            <span class="badge bg-warning text-dark mt-2 d-none editing-badge">Редактируется</span>
                        </div>

                        <div class="activity-log" data-text="Бюджетные страны Азии: где жить на 30 долларов в день. Вьетнам, Камбоджа, Лаос, Индонезия — цены на жильё, еду, транспорт и визы.">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <p class="mb-0">
                                    <span class="text-primary">#4221</span>
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
                            </div>
                            <p class="mb-1">Бюджетные страны Азии</p>
                            <div class="activity-meta">
                                <i class="bi bi-clock me-1"></i>Завтра, 12:00
                            </div>
                            <span class="badge bg-info mt-2">Запланировано</span>
                            <span class="badge bg-warning text-dark mt-2 d-none editing-badge">Редактируется</span>
                        </div>
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
                        <div class="activity-log" data-text="Пять островов Греции, куда хочется вернуться: Наксос, Парос, Milos, Фолегандрос и Амарго. Пляжи, еда, паромы и сколько стоит неделя на каждом.">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <p class="mb-0">
                                    <span class="text-primary">#4222</span>
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
                            <p class="mb-1">Пять островов Греции</p>
                            <div class="stacked-images sm mt-2">
                                <img src="<?= Yii::getAlias('@web/ui-kit/assets/images/user4.png') ?>" alt="Attachment">
                            </div>
                            <div class="activity-meta mt-2">
                                <i class="bi bi-clock me-1"></i>Черновик
                            </div>
                            <span class="badge bg-secondary mt-2">Черновик</span>
                            <span class="badge bg-warning text-dark mt-2 d-none editing-badge">Редактируется</span>
                        </div>

                        <div class="activity-log" data-text="Секреты дешёвых перелётов: как ловить ошибки тарифов, когда покупать билеты и какие сервисы мониторинга цен работают.">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <p class="mb-0">
                                    <span class="text-primary">#4223</span>
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
                            <p class="mb-1">Секреты дешёвых перелётов</p>
                            <div class="activity-meta">
                                <i class="bi bi-clock me-1"></i>Черновик
                            </div>
                            <span class="badge bg-secondary mt-2">Черновик</span>
                            <span class="badge bg-warning text-dark mt-2 d-none editing-badge">Редактируется</span>
                        </div>
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
    var preview = document.getElementById('publicationPreview');
    if (!source || !preview) {
        return;
    }
    var update = function () {
        preview.textContent = source.value || source.placeholder;
    };
    source.addEventListener('input', update);
    update();

    var editingLog = null;

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
        update();
        scrollToMiddle(log);
    };

    document.querySelectorAll('.activity-log').forEach(function (log) {
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
