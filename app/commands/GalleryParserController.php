<?php

declare(strict_types=1);

namespace app\commands;

use app\shared\Gallery\Service\GalleryScanService;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Runs the forum.awd.ru gallery album scan.
 *
 * Usage:
 *   yii gallery-parser/scan
 *   yii gallery-parser/scan --from=54900 --to=54910 --limit=5
 *   yii gallery-parser/scan --from=11186 --to=11186 --pageLimit=2
 */
final class GalleryParserController extends Controller
{
    public ?int $from = null;
    public ?int $to = null;
    public ?int $limit = null;
    public ?int $pageLimit = null;

    public function __construct(
        $id,
        $module,
        private readonly GalleryScanService $scanService,
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
            $this->stdout("Skipped: another gallery scan is already running\n");
            return ExitCode::OK;
        }
        $this->stdout(sprintf(
            "Processed: %d, saved: %d, updated: %d, not found: %d, login required: %d, images saved: %d, images updated: %d, failed: %d\n",
            $stats['processed'],
            $stats['saved'],
            $stats['updated'],
            $stats['not_found'],
            $stats['login_required'],
            $stats['images_saved'],
            $stats['images_updated'],
            $stats['failed'],
        ));
        return ExitCode::OK;
    }
}
