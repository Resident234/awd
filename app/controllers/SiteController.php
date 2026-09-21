<?php

declare(strict_types=1);

namespace app\controllers;

use Yii;
use app\models\ContactForm;
use app\models\LoginForm;
use app\shared\Forum\Contract\ForumRepositoryInterface;
use app\shared\Publications\Dto\ForumPublicationRef;
use app\shared\Publications\Service\PublicationsService;
use app\shared\Telegram\Infrastructure\TelegramApiException;
use app\shared\Telegram\Service\ChannelService;
use yii\captcha\CaptchaAction;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\base\Security;
use yii\mail\MailerInterface;
use yii\web\Controller;
use yii\web\ErrorAction;
use yii\web\Response;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class SiteController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly MailerInterface $mailer,
        private readonly Security $security,
        private readonly ChannelService $telegramChannel,
        private readonly PublicationsService $publications,
        private readonly ForumRepositoryInterface $forum,
        $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                    'publication-create' => ['post'],
                    'publication-to-draft' => ['post'],
                    'publication-publish' => ['post'],
                    'publication-delete' => ['post'],
                    'publication-schedule' => ['post'],
                    'forum-viewed' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions(): array
    {
        return [
            'error' => [
                'class' => ErrorAction::class,
            ],
            'captcha' => [
                'class' => CaptchaAction::class,
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
                'transparent' => true,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex(): string
    {
        $this->layout = 'dashboard';

        $channelConnected = false;
        try {
            $this->telegramChannel->channelInfo();
            $channelConnected = true;
        } catch (Throwable) {
            // Token is not configured yet or Telegram API is unreachable:
            // the dashboard still renders with the placeholder state.
        }

        return $this->render('index', [
            'channelConnected' => $channelConnected,
        ]);
    }

    /**
     * Displays the channel settings page.
     *
     * @return string
     */
    public function actionChannelSettings(): string
    {
        $this->layout = 'dashboard';

        $channelDescription = null;
        try {
            $channelDescription = $this->telegramChannel->channelInfo()->description;
        } catch (Throwable) {
            // Token is not configured yet or Telegram API is unreachable:
            // the page still renders with the placeholder state.
        }

        $publishedDescriptions = $this->telegramChannel->publishedDescriptions();

        return $this->render('channel-settings', [
            'channelDescription' => $channelDescription,
            'publishedDescriptions' => $publishedDescriptions,
        ]);
    }

    /**
     * Displays the channel publications page.
     *
     * @return string
     */
    public function actionPublications(): string
    {
        $this->layout = 'dashboard';

        // Merge URL params with session-stored filters:
        // URL params take precedence (user explicitly typed them).
        // Session provides persistence across form submits and navigation.
        $session = Yii::$app->session;
        $sessionFilters = $session->get('forumFilters', []);

        $withImagesOnly = (string)$this->request->get('withImages', '') === '1'
            ? true
            : ($sessionFilters['withImages'] ?? false);
        $withPostsOnly = (string)$this->request->get('withPosts', '') === '1'
            ? true
            : ($sessionFilters['withPosts'] ?? false);
        $imagesCountRaw = $this->request->get('imagesCount', '');
        $imagesCount = $imagesCountRaw !== ''
            ? (int)$imagesCountRaw
            : ($sessionFilters['imagesCount'] ?? 0);

        // Persist the effective filter state to the session so that any
        // subsequent form POST (which carries no URL params) can restore them.
        $effective = [];
        if ($withImagesOnly)  $effective['withImages']  = true;
        if ($withPostsOnly)   $effective['withPosts']   = true;
        if ($imagesCount > 0) $effective['imagesCount'] = $imagesCount;
        $session->set('forumFilters', $effective);

        return $this->render('publications', [
            'posts' => $this->publications->posts(),
            'drafts' => $this->publications->drafts(),
            'deleted' => $this->publications->deleted(),
            'topics' => $this->forum->latestTopicsWithPosts(10, 10, $withImagesOnly, $withPostsOnly, $imagesCount),
            'withImagesOnly' => $withImagesOnly,
            'withPostsOnly' => $withPostsOnly,
            'imagesCount' => $imagesCount,
            'now' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Saves the current forum filter state to the session via AJAX.
     * Used by the JS filter handler to persist filters across navigation.
     *
     * @return Response
     */
    public function actionForumFilterSave(): Response
    {
        $withImages = $this->request->post('withImages', '');
        $withPosts = $this->request->post('withPosts', '');
        $imagesCount = $this->request->post('imagesCount', '0');

        $filters = [];
        if ($withImages === '1') {
            $filters['withImages'] = true;
        }
        if ($withPosts === '1') {
            $filters['withPosts'] = true;
        }
        if ($imagesCount !== '' && (int)$imagesCount > 0) {
            $filters['imagesCount'] = (int)$imagesCount;
        }

        Yii::$app->session->set('forumFilters', $filters);

        return $this->asJson(['ok' => true]);
    }

    /**
     * Clears the forum filter state from the session and redirects back.
     *
     * @return Response
     */
    public function actionForumFilterClear(): Response
    {
        Yii::$app->session->remove('forumFilters');
        return $this->redirect(['publications']);
    }

    /**
     * Marks a forum topic or post as viewed by the "Просмотрено"
     * button: inserts a publications_topic_map / publications_post_map
     * row with an empty telegram_id, so the element stops showing up
     * among the unprocessed ones.
     *
     * @return Response
     */
    public function actionForumViewed(): Response
    {
        $id = (string)($this->request->post('forumEntityId', ''));
        $type = (string)($this->request->post('forumEntityType', ''));

        try {
            if ($type === 'topic') {
                $this->forum->markTopicViewed((int)$id);
                Yii::$app->session->setFlash('success', 'Топик отмечен как просмотренный.');
            } elseif ($type === 'post') {
                $this->forum->markPostViewed((int)$id);
                Yii::$app->session->setFlash('success', 'Пост отмечен как просмотренный.');
            } else {
                throw new InvalidArgumentException('Не указан элемент форума.');
            }
        } catch (InvalidArgumentException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->redirectWithFilters();
    }

    /**
     * Saves a publication from the new post form: "Опубликовать" and
     * "Сохранить" either create a new record or save an opened one —
     * cross-table moves between posts, drafts and the edited archive
     * are handled by the service. Nothing is sent to Telegram directly.
     *
     * @return Response
     */
    public function actionPublicationCreate(): Response
    {
        $text = (string)($this->request->post('publicationText', ''));
        $publishedAt = (string)($this->request->post('publicationAt', ''));
        $userTz = (string)($this->request->post('publicationTz', ''));
        $action = $this->request->post('action') === 'draft' ? 'draft' : 'publish';
        $source = (string)($this->request->post('publicationSource', 'new'));
        $sourceId = $this->request->post('publicationSourceId');
        $sourceId = $sourceId === null || $sourceId === '' ? null : (int)$sourceId;
        $forumRef = $this->forumRefFromRequest();
        $imageUrls = $this->imageUrlsFromRequest();

        try {
            if ($source === 'new') {
                if ($action === 'draft') {
                    $this->publications->saveDraft($text, $imageUrls, $forumRef);
                    Yii::$app->session->setFlash('success', 'Черновик сохранён.');
                } else {
                    $this->publications->schedulePost($text, $imageUrls, $publishedAt, $forumRef, $userTz ?: null);
                    Yii::$app->session->setFlash('success', 'Публикация сохранена и будет отправлена в канал в заданное время.');
                }
            } else {
                $this->publications->saveFromForm($text, $imageUrls, $publishedAt, $source, $sourceId, $action, $userTz ?: null);
                Yii::$app->session->setFlash('success', 'Изменения сохранены.');
            }
        } catch (InvalidArgumentException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->redirectWithFilters();
    }

    /**
     * Reads forum filters from the session and appends them to the redirect URL.
     * This ensures filters persist after any publication-related action.
     *
     * @return Response
     */
    private function redirectWithFilters(): Response
    {
        $request = Yii::$app->request;
        $session = Yii::$app->session;

        // Prefer URL params (direct navigation or same-page change)
        $withImages = $request->get('withImages');
        $withPosts = $request->get('withPosts');
        $imagesCount = $request->get('imagesCount');

        // If no URL params (e.g. after a POST submit), fall back to session
        if ($withImages === null) {
            $filters = $session->get('forumFilters', []);
            $withImages  = $filters['withImages']  ?? null;
            $withPosts   = $filters['withPosts']   ?? null;
            $imagesCount = $filters['imagesCount'] ?? null;
        }

        $url = ['publications'];
        if ($withImages === '1') {
            $url['withImages'] = '1';
        }
        if ($withPosts === '1') {
            $url['withPosts'] = '1';
        }
        if ($imagesCount !== null && $imagesCount !== '' && (int)$imagesCount > 0) {
            $url['imagesCount'] = (int)$imagesCount;
        }

        return $this->redirect($url);
    }

    /**
     * The attached images field of the publication form: one URL per
     * line, empty lines are dropped.
     *
     * @return string[]
     */
    private function imageUrlsFromRequest(): array
    {
        $raw = (string)($this->request->post('publicationImages', ''));

        $urls = array_map(
            static fn (string $line): string => trim($line),
            explode("\n", str_replace("\r\n", "\n", $raw)),
        );

        return array_values(array_filter($urls, static fn (string $url): bool => $url !== ''));
    }

    /**
     * The hidden forum source fields filled by the "Опубликовать"
     * button on a forum topic or post: tells the service which map
     * row to link the new publication with.
     */
    private function forumRefFromRequest(): ?ForumPublicationRef
    {
        $type = (string)($this->request->post('forumEntityType', ''));
        $id = (string)($this->request->post('forumEntityId', ''));

        if (!in_array($type, ['topic', 'post'], true) || $id === '') {
            return null;
        }

        return new ForumPublicationRef($type, (int)$id);
    }

    /**
     * Publishes a record immediately by the "Опубликовать" button on a
     * list item: a draft moves to the posts table, a scheduled post
     * stops being scheduled — in both cases published_at and
     * updated_at become the current time. Nothing is sent to Telegram
     * directly; the periodic task picks the record up.
     *
     * @return Response
     */
    public function actionPublicationPublish(): Response
    {
        $id = (string)($this->request->post('publicationId', ''));
        $source = (string)($this->request->post('publicationSource', ''));

        try {
            if ($source === 'draft') {
                $this->publications->publishDraft((int)$id);
            } elseif ($source === 'post') {
                $this->publications->publishPostNow((int)$id);
            } elseif ($source === 'deleted') {
                $this->publications->publishDeleted((int)$id);
            } else {
                throw new InvalidArgumentException('Не указана публикуемая запись.');
            }
            Yii::$app->session->setFlash('success', 'Публикация сохранена и будет отправлена в канал.');
        } catch (InvalidArgumentException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->redirectWithFilters();
    }

    /**
     * Moves a post to drafts by the "Переместить в черновик" button:
     * the record leaves the posts table and appears in the drafts
     * table with created_at preserved and updated_at set to now.
     * The same button on a soft-deleted record restores it from
     * publications_deleted back to the drafts table.
     *
     * @return Response
     */
    public function actionPublicationToDraft(): Response
    {
        $id = (string)($this->request->post('publicationId', ''));
        $source = (string)($this->request->post('publicationSource', ''));

        try {
            if ($source === 'deleted') {
                $this->publications->moveDeletedToDraft((int)$id);
                Yii::$app->session->setFlash('success', 'Удалённая запись перемещена в черновики.');
            } else {
                $this->publications->movePostToDraft((int)$id);
                Yii::$app->session->setFlash('success', 'Публикация перемещена в черновики.');
            }
        } catch (InvalidArgumentException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->redirectWithFilters();
    }

    /**
     * Schedules a draft or a soft-deleted record by the
     * "Запланировать публикацию" modal: the record moves to the
     * posts table with the publication time taken from the modal's
     * date-time field.
     *
     * @return Response
     */
    public function actionPublicationSchedule(): Response
    {
        $id = (string)($this->request->post('publicationId', ''));
        $publishedAt = (string)($this->request->post('publicationAt', ''));
        $userTz = (string)($this->request->post('publicationTz', ''));
        $source = (string)($this->request->post('publicationSource', 'draft'));

        try {
            if ($source === 'deleted') {
                $this->publications->scheduleDeleted($id === '' ? 0 : (int)$id, $publishedAt, $userTz ?: null);
            } else {
                $this->publications->scheduleDraft($id === '' ? 0 : (int)$id, $publishedAt, $userTz ?: null);
            }
            Yii::$app->session->setFlash('success', 'Публикация запланирована.');
        } catch (InvalidArgumentException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->redirectWithFilters();
    }

    /**
     * Soft-deletes a record by the "Удалить" button on a list item:
     * the record leaves its table (posts or drafts) and moves to
     * publications_deleted with created_at and published_at
     * preserved, updated_at set to now and deleted_at left empty —
     * the periodic task removes the channel message and stamps it.
     *
     * @return Response
     */
    public function actionPublicationDelete(): Response
    {
        $id = (string)($this->request->post('publicationId', ''));
        $source = (string)($this->request->post('publicationSource', ''));

        try {
            if ($source === 'draft') {
                $this->publications->deleteDraft((int)$id);
            } elseif ($source === 'post') {
                $this->publications->deletePost((int)$id);
            } else {
                throw new InvalidArgumentException('Не указана удаляемая запись.');
            }
            Yii::$app->session->setFlash('success', 'Запись удалена.');
        } catch (InvalidArgumentException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->redirectWithFilters();
    }

    /**
     * Updates the TRVL channel description from the channel settings page.
     *
     * @return Response
     */
    public function actionChannelDescription(): Response
    {
        $description = (string)($this->request->post('channelDescription', ''));

        try {
            $this->telegramChannel->updateDescription($description);
        } catch (InvalidArgumentException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());

            return $this->redirect(['channel-settings']);
        } catch (TelegramApiException $e) {
            Yii::$app->session->setFlash(
                'error',
                "Telegram API error [{$e->errorCode}]: {$e->getMessage()}",
            );

            return $this->redirect(['channel-settings']);
        } catch (RuntimeException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());

            return $this->redirect(['channel-settings']);
        }

        Yii::$app->session->setFlash('success', 'Описание канала обновлено.');

        return $this->redirect(['channel-settings']);
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin(): Response|string
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm($this->security);

        if ($model->load($this->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';

        return $this->render('login', ['model' => $model]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout(): Response
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact(): Response|string
    {
        $model = new ContactForm();

        $contact = $model->load($this->request->post()) && $model->contact(
            $this->mailer,
            Yii::$app->params['adminEmail'],
            Yii::$app->params['senderEmail'],
            Yii::$app->params['senderName'],
        );

        if ($contact) {
            Yii::$app->session->setFlash(
                'success',
                'Thank you for contacting us. We will respond to you as soon as possible.',
            );

            return $this->refresh();
        }

        return $this->render('contact', ['model' => $model]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout(): string
    {
        return $this->render('about');
    }

    /**
     * Returns the user's preferred timezone from the cookie, falling back to
     * UTC for display purposes (consistent with client-side detection).
     *
     * @return string
     */
    public function getUserDisplayTimezone(): string
    {
        try {
            $cookie = Yii::$app->request->cookies->get('portal_tz');
            if ($cookie !== null && $cookie->value !== '') {
                new DateTimeZone($cookie->value); // validate
                return $cookie->value;
            }
        } catch (\Exception $e) {
            // invalid timezone in cookie, fall through
        }
        return 'UTC';
    }
}
