<?php

declare(strict_types=1);

namespace app\commands;

use app\shared\Forum\Service\ForumPostScanService;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Runs the forum.awd.ru topic post scan: walks every page of each
 * stored topic through the pagination and saves all posts.
 *
 * Usage:
 *   yii forum-post-parser/scan
 *   yii forum-post-parser/scan --from=415949 --to=415949
 *   yii forum-post-parser/scan --from=415949 --to=416000 --limit=5 --page-limit=2
 */
final class ForumPostParserController extends Controller
{
    public ?int $from = null;
    public ?int $to = null;
    public ?int $limit = null;
    public ?int $pageLimit = null;

    public function __construct(
        $id,
        $module,
        private readonly ForumPostScanService $scanService,
        array $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), ['from', 'to', 'limit', 'pageLimit']);
    }

    public function actionScan(): int
    {
        $stats = $this->scanService->run($this->from, $this->to, $this->limit, $this->pageLimit);
        if ($stats === null) {
            $this->stdout("Skipped: another forum post scan is already running\n");
            return ExitCode::OK;
        }
        $this->stdout(sprintf(
            "Topics: %d, pages: %d, posts saved: %d, posts updated: %d, no posts: %d, not found: %d, login required: %d, failed: %d\n",
            $stats['processed'],
            $stats['pages'],
            $stats['posts_saved'],
            $stats['posts_updated'],
            $stats['topics_skipped_no_posts'],
            $stats['topics_not_found'],
            $stats['topics_login_required'],
            $stats['topics_failed'],
        ));
        return ExitCode::OK;
    }
}
