<?php

declare(strict_types=1);

namespace app\controllers;

use Yii;
use app\models\ContactForm;
use app\models\LoginForm;
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

        return $this->render('publications', [
            'posts' => $this->publications->posts(),
            'drafts' => $this->publications->drafts(),
            'now' => gmdate('Y-m-d H:i:s'),
        ]);
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
        $action = $this->request->post('action') === 'draft' ? 'draft' : 'publish';
        $source = (string)($this->request->post('publicationSource', 'new'));
        $sourceId = $this->request->post('publicationSourceId');
        $sourceId = $sourceId === null || $sourceId === '' ? null : (int)$sourceId;

        try {
            if ($source === 'new') {
                if ($action === 'draft') {
                    $this->publications->saveDraft($text, []);
                    Yii::$app->session->setFlash('success', 'Черновик сохранён.');
                } else {
                    $this->publications->schedulePost($text, [], $publishedAt);
                    Yii::$app->session->setFlash('success', 'Публикация сохранена и будет отправлена в канал в заданное время.');
                }
            } else {
                $this->publications->saveFromForm($text, [], $publishedAt, $source, $sourceId, $action);
                Yii::$app->session->setFlash('success', 'Изменения сохранены.');
            }
        } catch (InvalidArgumentException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->redirect(['publications']);
    }

    /**
     * Moves a post to drafts by the "Переместить в черновик" button:
     * the record leaves the posts table and appears in the drafts
     * table with created_at preserved and updated_at set to now.
     *
     * @return Response
     */
    public function actionPublicationToDraft(): Response
    {
        $id = (string)($this->request->post('publicationId', ''));

        try {
            $this->publications->movePostToDraft((int)$id);
            Yii::$app->session->setFlash('success', 'Публикация перемещена в черновики.');
        } catch (InvalidArgumentException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->redirect(['publications']);
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
}
