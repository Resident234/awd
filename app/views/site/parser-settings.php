<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var array<string, mixed> $tunables */
/** @var array<int, array<string, mixed>> $rows */

use app\shared\Forum\Service\ParserSettingsService;
use yii\helpers\Html;

$this->title = 'Настройки парсера';
$bounds = ParserSettingsService::bounds();
$labels = ParserSettingsService::labels();
$displayFormat = 'php:' . $this->context->publicationSettings()['dateFormat'];
?>
<!-- Row start -->
<div class="row">
    <div class="col-12">
        <form method="post" action="<?= \yii\helpers\Url::to(['site/parser-settings-save']) ?>">
            <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>"
                   value="<?= Yii::$app->request->csrfToken ?>">
            <div class="row">
                <div class="col-xxl-7 col-sm-12 col-12">

                    <!-- How the scan talks to the forum -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title">Запрос к форуму</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-3">
                                <i class="bi bi-info-circle me-1"></i>
                                Одни и те же числа действуют для всех парсеров: они описывают,
                                как клиент ждёт ответ и как упорствует, пока тот не придёт.
                            </p>

                            <?php foreach ($bounds as $code => $bound): ?>
                                <div class="mb-3">
                                    <label class="form-label" for="tunable-<?= $code ?>">
                                        <?= Html::encode($labels[$code]) ?>
                                    </label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="tunable-<?= $code ?>"
                                               name="tunables[<?= $code ?>]"
                                               value="<?= (int)$tunables[$code] ?>"
                                               min="<?= (int)$bound['min'] ?>" max="<?= (int)$bound['max'] ?>"
                                               step="1" required>
                                        <span class="input-group-text"><?= Html::encode($bound['unit']) ?></span>
                                    </div>
                                </div>
                            <?php endforeach ?>

                            <div class="mb-3">
                                <label class="form-label" for="tunable-http_banned_statuses">
                                    <?= Html::encode($labels['http_banned_statuses']) ?>
                                </label>
                                <input type="text" class="form-control" id="tunable-http_banned_statuses"
                                       name="tunables[http_banned_statuses]"
                                       value="<?= Html::encode((string)$tunables['http_banned_statuses']) ?>"
                                       placeholder="429,500,502,503,504" required>
                                <small class="text-muted d-block mt-1">
                                    Через запятую. На эти ответы запрос повторяется, остальное
                                    считается ошибкой сразу.
                                </small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="tunable-login_url">
                                    <?= Html::encode($labels['login_url']) ?>
                                </label>
                                <input type="url" class="form-control" id="tunable-login_url"
                                       name="tunables[login_url]"
                                       value="<?= Html::encode((string)$tunables['login_url']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="tunable-source_timezone">
                                    <?= Html::encode($labels['source_timezone']) ?>
                                </label>
                                <select class="form-select" id="tunable-source_timezone" name="tunables[source_timezone]">
                                    <?php foreach (DateTimeZone::listIdentifiers() as $timezone): ?>
                                        <option value="<?= Html::encode($timezone) ?>" <?= (string)$tunables['source_timezone'] === $timezone ? 'selected' : '' ?>>
                                            <?= Html::encode($timezone) ?>
                                        </option>
                                    <?php endforeach ?>
                                </select>
                                <small class="text-muted d-block mt-1">
                                    В поясах форума даты постов написаны как есть: парсер
                                    считает их временем источника, а не временем браузера.
                                </small>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="col-xxl-5 col-sm-12 col-12">

                    <!-- Ranges of entity ids each scan walks -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title">Диапазоны источников</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-3">
                                <i class="bi bi-info-circle me-1"></i>
                                Каждый парсер ходит по своим идентификаторам сущностей и
                                собирает адрес страницы из начала и номера.
                            </p>

                            <?php foreach ($rows as $row): ?>
                                <div class="border rounded p-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-bold"><?= Html::encode($row['code']) ?></span>
                                        <label class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" role="switch"
                                                   name="rows[<?= Html::encode($row['code']) ?>][isActive]" value="1"
                                                   <?= $row['isActive'] ? 'checked' : '' ?>>
                                            <span class="form-check-label small">Активен</span>
                                        </label>
                                    </div>
                                    <input type="hidden" name="rows[<?= Html::encode($row['code']) ?>][id]"
                                           value="<?= (int)$row['id'] ?>">
                                    <div class="mb-2">
                                        <label class="form-label small mb-1"
                                               for="row-<?= Html::encode($row['code']) ?>-url">Адрес сущности</label>
                                        <input type="url" class="form-control form-control-sm"
                                               id="row-<?= Html::encode($row['code']) ?>-url"
                                               name="rows[<?= Html::encode($row['code']) ?>][baseUrl]"
                                               value="<?= Html::encode((string)$row['baseUrl']) ?>" required>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label small mb-1"
                                                   for="row-<?= Html::encode($row['code']) ?>-from">Начало</label>
                                            <input type="number" class="form-control form-control-sm"
                                                   id="row-<?= Html::encode($row['code']) ?>-from"
                                                   name="rows[<?= Html::encode($row['code']) ?>][tFrom]"
                                                   value="<?= (int)$row['tFrom'] ?>" min="0" step="1" required>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small mb-1"
                                                   for="row-<?= Html::encode($row['code']) ?>-to">Конец</label>
                                            <input type="number" class="form-control form-control-sm"
                                                   id="row-<?= Html::encode($row['code']) ?>-to"
                                                   name="rows[<?= Html::encode($row['code']) ?>][tTo]"
                                                   value="<?= (int)$row['tTo'] ?>" min="0" step="1" required>
                                        </div>
                                    </div>
                                    <small class="text-muted d-block mt-2">
                                        Последний запуск:
                                        <?= $row['lastRunAt'] !== null
                                            ? Yii::$app->formatter->asDatetime((string)$row['lastRunAt'], $displayFormat, $this->context->getUserDisplayTimezone())
                                            : 'ещё не запускался' ?>
                                    </small>
                                </div>
                            <?php endforeach ?>
                        </div>
                    </div>

                </div>
            </div>

            <button type="submit" class="btn btn-primary mb-4">
                <i class="bi bi-check2 me-1"></i>Сохранить настройки парсера
            </button>
        </form>
    </div>
</div>
<!-- Row end -->
