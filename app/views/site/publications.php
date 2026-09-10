<?php

declare(strict_types=1);

/** @var yii\web\View $this */

$this->title = 'Публикации в канал';
?>
<!-- Row start -->
<div class="row">
    <div class="col-xxl-7 col-sm-12 col-12">

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

        <!-- Publications -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title">Публикации</h5>
            </div>
            <div class="card-body">
                <div class="scroll350">

                    <!-- Timeline start -->
                    <div class="m-0">
                        <div class="activity-log">
                            <p class="mb-1">
                                <span class="text-primary">#4218</span> — 10 маршрутов по Грузии
                            </p>
                            <div class="activity-meta">
                                <i class="bi bi-clock me-1"></i>Сегодня, 18:00
                            </div>
                            <span class="badge bg-success mt-2">Опубликовано</span>
                        </div>

                        <div class="activity-log">
                            <p class="mb-1">
                                <span class="text-primary">#4219</span> — Как собрать рюкзак в поход
                            </p>
                            <div class="stacked-images sm mt-2">
                                <img src="<?= Yii::getAlias('@web/ui-kit/assets/images/user.png') ?>" alt="Attachment">
                                <img src="<?= Yii::getAlias('@web/ui-kit/assets/images/user2.png') ?>" alt="Attachment">
                            </div>
                            <div class="activity-meta mt-2">
                                <i class="bi bi-clock me-1"></i>Сегодня, 21:30
                            </div>
                            <span class="badge bg-info mt-2">Запланировано</span>
                        </div>

                        <div class="activity-log">
                            <p class="mb-1">
                                <span class="text-primary">#4220</span> — Ночной Стамбул: маршрут выходного дня
                            </p>
                            <div class="stacked-images sm mt-2">
                                <img src="<?= Yii::getAlias('@web/ui-kit/assets/images/user3.png') ?>" alt="Attachment">
                            </div>
                            <div class="activity-meta mt-2">
                                <i class="bi bi-clock me-1"></i>Завтра, 09:00
                            </div>
                            <span class="badge bg-info mt-2">Запланировано</span>
                        </div>

                        <div class="activity-log">
                            <p class="mb-1">
                                <span class="text-primary">#4221</span> — Бюджетные страны Азии
                            </p>
                            <div class="activity-meta">
                                <i class="bi bi-clock me-1"></i>Завтра, 12:00
                            </div>
                            <span class="badge bg-info mt-2">Запланировано</span>
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
                        <div class="activity-log">
                            <p class="mb-1">
                                <span class="text-primary">#4222</span> — Пять островов Греции
                            </p>
                            <div class="stacked-images sm mt-2">
                                <img src="<?= Yii::getAlias('@web/ui-kit/assets/images/user4.png') ?>" alt="Attachment">
                            </div>
                            <div class="activity-meta mt-2">
                                <i class="bi bi-clock me-1"></i>Черновик
                            </div>
                            <span class="badge bg-secondary mt-2">Черновик</span>
                        </div>

                        <div class="activity-log">
                            <p class="mb-1">
                                <span class="text-primary">#4223</span> — Секреты дешёвых перелётов
                            </p>
                            <div class="activity-meta">
                                <i class="bi bi-clock me-1"></i>Черновик
                            </div>
                            <span class="badge bg-secondary mt-2">Черновик</span>
                        </div>
                    </div>
                    <!-- Timeline end -->

                </div>
            </div>
        </div>

    </div>
</div>
<!-- Row end -->
