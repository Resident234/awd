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
use app\widgets\Alert;
use yii\captcha\CaptchaAction;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\base\Security;
use yii\helpers\Url;
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
     * Displays the channel publications page. The session holds the forum
     * filter state and the address bar is kept as its mirror image, so a page
     * that was opened on a URL disagreeing with the session is redirected onto
     * the address the session describes.
     *
     * @return Response|string
     */
    public function actionPublications(): Response|string
    {
        $this->layout = 'dashboard';

        $this->syncForumFilters();
        if ($this->normalizeForumFilters($this->request->get()) !== $this->forumFilters()) {
            return $this->redirect($this->forumFilterUrl());
        }

        return $this->render('publications', $this->publicationsData());
    }

    /**
     * Resolves the effective forum filters. A filter that is already active in
     * the session wins, so reloading or navigating cannot switch it back on;
     * only an empty session adopts the query parameters of the request.
     */
    private function syncForumFilters(): void
    {
        $session = Yii::$app->session;
        $stored = $this->normalizeForumFilters((array)$session->get('forumFilters', []));

        if ($this->hasActiveForumFilter($stored)) {
            $session->set('forumFilters', $stored);
            return;
        }

        $session->set('forumFilters', $this->normalizeForumFilters($this->request->get()));
    }

    /**
     * @param array<string, mixed> $raw
     * @return array{withImages: bool, withPosts: bool, imagesCount: int}
     */
    private function normalizeForumFilters(array $raw): array
    {
        $imagesCount = (int)($raw['imagesCount'] ?? 0);

        return [
            'withImages' => (string)($raw['withImages'] ?? '') === '1',
            'withPosts' => (string)($raw['withPosts'] ?? '') === '1',
            'imagesCount' => $imagesCount > 0 ? $imagesCount : 0,
        ];
    }

    /**
     * @param array{withImages: bool, withPosts: bool, imagesCount: int} $filters
     */
    private function hasActiveForumFilter(array $filters): bool
    {
        return $filters['withImages']
            || $filters['withPosts']
            || $filters['imagesCount'] > 0;
    }

    /**
     * The publications route with the filters of the current session as its
     * query, which is the address the bar has to show for that state.
     *
     * @return array<int|string, string>
     */
    private function forumFilterUrl(): array
    {
        $filters = $this->forumFilters();
        $url = ['publications'];

        if ($filters['withImages']) {
            $url['withImages'] = '1';
        }
        if ($filters['withPosts']) {
            $url['withPosts'] = '1';
        }
        if ($filters['imagesCount'] > 0) {
            $url['imagesCount'] = (string)$filters['imagesCount'];
        }

        return $url;
    }

    /**
     * @return array{withImages: bool, withPosts: bool, imagesCount: int}
     */
    private function forumFilters(): array
    {
        return $this->normalizeForumFilters((array)Yii::$app->session->get('forumFilters', []));
    }

    /**
     * Everything the publications page and its refreshable blocks render from.
     *
     * @return array<string, mixed>
     */
    private function publicationsData(): array
    {
        $filters = $this->forumFilters();

        return [
            'posts' => $this->publications->posts(),
            'drafts' => $this->publications->drafts(),
            'deleted' => $this->publications->deleted(),
            'topics' => $this->forum->latestTopicsWithPosts(
                10,
                10,
                $filters['withImages'],
                $filters['withPosts'],
                $filters['imagesCount'],
            ),
            'withImagesOnly' => $filters['withImages'],
            'withPostsOnly' => $filters['withPosts'],
            'imagesCount' => $filters['imagesCount'],
            'now' => gmdate('Y-m-d H:i:s'),
        ];
    }

    /**
     * Re-renders the four lists the publications page updates in place. Every
     * mutation is reported against all of them because a single action can
     * move a record across the posts, drafts and deleted tables at once
     * (see PublicationsService::saveFromForm).
     *
     * @return array<string, string>
     */
    private function renderPublicationBlocks(): array
    {
        $data = $this->publicationsData();
        $blocks = [];

        foreach (['forum', 'posts', 'drafts', 'deleted'] as $block) {
            $blocks[$block] = $this->renderPartial('_block_' . $block, $data);
        }

        return $blocks;
    }

    /**
     * Ends a publications mutation: refreshed blocks as JSON for the AJAX
     * caller, the regular redirect otherwise so the page keeps working
     * without JavaScript.
     */
    private function finishPublicationsRequest(bool $ok): Response
    {
        if (!$this->request->getIsAjax()) {
            return $this->redirectWithFilters();
        }

        return $this->asJson([
            'ok' => $ok,
            'blocks' => $this->renderPublicationBlocks(),
            'flash' => $this->renderFlash(),
        ]);
    }

    /**
     * Renders the flash container the AJAX responses swap in place of the
     * redirect that would otherwise have carried the message to the next
     * page view.
     */
    private function renderFlash(): string
    {
        return Alert::widget();
    }

    /**
     * Saves the current forum filter state to the session via AJAX and answers
     * with the address that mirrors it, so the URL follows every session change
     * instead of being rebuilt by the caller.
     *
     * @return Response
     */
    public function actionForumFilterSave(): Response
    {
        Yii::$app->session->set(
            'forumFilters',
            $this->normalizeForumFilters($this->request->post()),
        );

        return $this->asJson([
            'ok' => true,
            'url' => Url::to($this->forumFilterUrl()),
            'blocks' => ['forum' => $this->renderPartial('_block_forum', $this->publicationsData())],
        ]);
    }

    /**
     * Clears the forum filter state from the session and redirects back.
     *
     * @return Response
     */
    public function actionForumFilterClear(): Response
    {
        Yii::$app->session->remove('forumFilters');

        if ($this->request->getIsAjax()) {
            return $this->asJson([
                'ok' => true,
                'url' => Url::to(['publications']),
                'blocks' => ['forum' => $this->renderPartial('_block_forum', $this->publicationsData())],
            ]);
        }

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

        $ok = false;
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
            $ok = true;
        } catch (InvalidArgumentException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->finishPublicationsRequest($ok);
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
        // Browsers put CRLF into the wire form of a textarea, so the same text
        // would gain invisible chars on every save; Telegram and the block
        // rendering both count line breaks as a single "\n".
        $text = str_replace(["\r\n", "\r"], "\n", (string)($this->request->post('publicationText', '')));
        $publishedAt = (string)($this->request->post('publicationAt', ''));
        $userTz = (string)($this->request->post('publicationTz', ''));
        $action = $this->request->post('action') === 'draft' ? 'draft' : 'publish';
        $source = (string)($this->request->post('publicationSource', 'new'));
        $sourceId = $this->request->post('publicationSourceId');
        $sourceId = $sourceId === null || $sourceId === '' ? null : (int)$sourceId;
        $forumRef = $this->forumRefFromRequest();
        $imageUrls = $this->imageUrlsFromRequest();

        $ok = false;
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
            $ok = true;
        } catch (InvalidArgumentException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->finishPublicationsRequest($ok);
    }

    /**
     * Reopens the publications page on the address the filters of the session
     * describe, so a mutation does not drop them from the bar.
     *
     * @return Response
     */
    private function redirectWithFilters(): Response
    {
        return $this->redirect($this->forumFilterUrl());
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

        $ok = false;
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
            $ok = true;
        } catch (InvalidArgumentException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->finishPublicationsRequest($ok);
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

        $ok = false;
        try {
            if ($source === 'deleted') {
                $this->publications->moveDeletedToDraft((int)$id);
                Yii::$app->session->setFlash('success', 'Удалённая запись перемещена в черновики.');
            } else {
                $this->publications->movePostToDraft((int)$id);
                Yii::$app->session->setFlash('success', 'Публикация перемещена в черновики.');
            }
            $ok = true;
        } catch (InvalidArgumentException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->finishPublicationsRequest($ok);
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

        $ok = false;
        try {
            if ($source === 'deleted') {
                $this->publications->scheduleDeleted($id === '' ? 0 : (int)$id, $publishedAt, $userTz ?: null);
            } else {
                $this->publications->scheduleDraft($id === '' ? 0 : (int)$id, $publishedAt, $userTz ?: null);
            }
            Yii::$app->session->setFlash('success', 'Публикация запланирована.');
            $ok = true;
        } catch (InvalidArgumentException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->finishPublicationsRequest($ok);
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

        $ok = false;
        try {
            if ($source === 'draft') {
                $this->publications->deleteDraft((int)$id);
            } elseif ($source === 'post') {
                $this->publications->deletePost((int)$id);
            } else {
                throw new InvalidArgumentException('Не указана удаляемая запись.');
            }
            Yii::$app->session->setFlash('success', 'Запись удалена.');
            $ok = true;
        } catch (InvalidArgumentException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->finishPublicationsRequest($ok);
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
