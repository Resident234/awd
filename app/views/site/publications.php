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
</div>
<!-- Row end -->
