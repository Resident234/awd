<?php

declare(strict_types=1);

namespace app\widgets;

use Yii;
use yii\helpers\Html;

/**
 * Alert widget renders a message from session flash using UI Kit alert components.
 * All flash messages are displayed in the sequence they were assigned using setFlash.
 *
 * ```php
 * Yii::$app->session->setFlash('error', 'This is the message');
 * Yii::$app->session->setFlash('success', 'This is the message');
 * Yii::$app->session->setFlash('info', 'This is the message');
 * ```
 *
 * Multiple messages could be set as follows:
 *
 * ```php
 * Yii::$app->session->setFlash('error', ['Error 1', 'Error 2']);
 * ```
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @author Alexander Makarov <sam@rmcreative.ru>
 */
class Alert extends \yii\bootstrap5\Widget
{
    /**
     * @var array the alert types configuration for the flash messages.
     * This array is setup as $key => $value, where:
     * - key: the name of the session flash variable
     * - value: the bootstrap alert type (i.e. danger, success, info, warning)
     */
    public array $alertTypes = [
        'error'   => 'alert-danger',
        'danger'  => 'alert-danger',
        'success' => 'alert-success',
        'info'    => 'alert-info',
        'warning' => 'alert-warning'
    ];

    /**
     * @var array Bootstrap icons for each alert type.
     * Key matches the alert type, value is the Bootstrap icon class.
     * Using UI Kit style icons: bi-check2-circle, bi-x-circle, bi-info-circle, bi-exclamation-triangle
     */
    public array $alertIcons = [
        'error'   => 'bi bi-x-circle',
        'danger'  => 'bi bi-x-circle',
        'success' => 'bi bi-check2-circle',
        'info'    => 'bi bi-info-circle',
        'warning' => 'bi bi-exclamation-triangle'
    ];

    /**
     * @var array Strong text prefixes for each alert type.
     * Key matches the alert type, value is the prefix text.
     */
    public array $alertPrefixes = [
        'error'   => 'Error:',
        'danger'  => 'Error:',
        'success' => 'Action Completed:',
        'info'    => 'Info:',
        'warning' => 'Warning:'
    ];

    /**
     * @var array the options for rendering the close button tag.
     */
    public array $closeButton = [];

    /**
     * {@inheritdoc}
     */
    public function run(): void
    {
        $session = Yii::$app->session;

        if (!$session->getIsActive() && !$session->getHasSessionId()) {
            return;
        }

        $appendClass = isset($this->options['class']) ? ' ' . $this->options['class'] : '';

        foreach (array_keys($this->alertTypes) as $type) {
            $flash = $session->getFlash($type);

            foreach ((array) $flash as $i => $message) {
                $alertClass = 'alert ' . $this->alertTypes[$type] . $appendClass;
                $iconClass = $this->alertIcons[$type] ?? 'bi bi-info-circle';
                $prefix = $this->alertPrefixes[$type] ?? 'Info:';

                // Build the alert content matching UI Kit structure
                // <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                //   <i class="bi bi-check2-circle me-2 fs-4 lh-1"></i>
                //   <div>
                //     <strong>Action Completed:</strong> Your data has been successfully saved...
                //   </div>
                //   <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                // </div>

                $icon = Html::tag('i', '', [
                    'class' => $iconClass . ' me-2 fs-4 lh-1',
                ]);

                $messageDiv = Html::tag('div', 
                    Html::tag('strong', $prefix) . ' ' . $message
                );

                $closeButton = $this->closeButton !== false
                    ? Html::tag('button', '', [
                        'type' => 'button',
                        'class' => 'btn-close ms-auto',
                        'data-bs-dismiss' => 'alert',
                        'aria-label' => Yii::t('yii/bootstrap5', 'Close'),
                    ])
                    : '';

                echo Html::tag('div',
                    $icon . $messageDiv . $closeButton,
                    [
                        'id' => $this->getId() . '-' . $type . '-' . $i,
                        'class' => $alertClass . ' alert-dismissible fade show d-flex align-items-center' . $appendClass,
                        'role' => 'alert',
                    ]
                );
            }

            $session->removeFlash($type);
        }
    }
}
