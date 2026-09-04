<?php

declare(strict_types=1);

namespace app\commands;

use app\shared\Forum\Service\ForumScanService;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Runs the forum.awd.ru topic scan.
 *
 * Usage:
 *   yii forum-parser/scan
 *   yii forum-parser/scan --from=441000 --to=442000 --limit=10
 */
final class ForumParserController extends Controller
{
    public ?int $from = null;
    public ?int $to = null;
    public ?int $limit = null;

    public function __construct(
        $id,
        $module,
        private readonly ForumScanService $scanService,
        array $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), ['from', 'to', 'limit']);
    }

    public function actionScan(): int
    {
        $stats = $this->scanService->run($this->from, $this->to, $this->limit);
        if ($stats === null) {
            $this->stdout("Skipped: another forum scan is already running\n");
            return ExitCode::OK;
        }
        $this->stdout(sprintf(
            "Processed: %d, saved: %d, updated: %d, not found: %d, login required: %d, failed: %d\n",
            $stats['processed'],
            $stats['saved'],
            $stats['updated'],
            $stats['not_found'],
            $stats['login_required'],
            $stats['failed'],
        ));
        return ExitCode::OK;
    }
}
