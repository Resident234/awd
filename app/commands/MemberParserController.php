<?php

declare(strict_types=1);

namespace app\commands;

use app\shared\Forum\Service\MemberScanService;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Runs the forum.awd.ru member profile scan: fetches the main profile
 * page and the statistics tab of every member in the configured range.
 *
 * Usage:
 *   yii member-parser/scan
 *   yii member-parser/scan --from=125070 --to=125080 --limit=5
 */
final class MemberParserController extends Controller
{
    public ?int $from = null;
    public ?int $to = null;
    public ?int $limit = null;

    public function __construct(
        $id,
        $module,
        private readonly MemberScanService $scanService,
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
            $this->stdout("Skipped: another member scan is already running\n");
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
