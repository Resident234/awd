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
            <div class="card-body pt-0">
                <div class="table-outer">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead>
                            <tr>
                                <th scope="col">ID в Telegram</th>
                                <th scope="col">Изображения</th>
                                <th scope="col">Текст</th>
                                <th scope="col">Время публикации</th>
                                <th scope="col">Статус</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>#4218</td>
                                <td><i class="bi bi-card-image fs-5 text-secondary"></i></td>
                                <td>10 маршрутов по Грузии</td>
                                <td>Сегодня, 18:00</td>
                                <td><span class="badge bg-success">Опубликовано</span></td>
                            </tr>
                            <tr>
                                <td>#4219</td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <i class="bi bi-card-image fs-5 text-secondary"></i>
                                        <i class="bi bi-card-image fs-5 text-secondary"></i>
                                    </div>
                                </td>
                                <td>Как собрать рюкзак в поход</td>
                                <td>Сегодня, 21:30</td>
                                <td><span class="badge bg-info">Запланировано</span></td>
                            </tr>
                            <tr>
                                <td>#4220</td>
                                <td><i class="bi bi-card-image fs-5 text-secondary"></i></td>
                                <td>Ночной Стамбул: маршрут выходного дня</td>
                                <td>Завтра, 09:00</td>
                                <td><span class="badge bg-info">Запланировано</span></td>
                            </tr>
                            <tr>
                                <td>#4221</td>
                                <td></td>
                                <td>Бюджетные страны Азии</td>
                                <td>Завтра, 12:00</td>
                                <td><span class="badge bg-info">Запланировано</span></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
<!-- Row end -->
