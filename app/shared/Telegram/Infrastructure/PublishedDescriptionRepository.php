<?php

declare(strict_types=1);

namespace app\shared\Telegram\Infrastructure;

use app\shared\Telegram\Contract\PublishedDescriptionRepositoryInterface;
use app\shared\Telegram\Dto\PublishedDescriptionData;
use PDO;
use yii\db\Connection;

/**
 * PostgreSQL storage for the published channel description archive.
 * All SQL lives here: upper layers receive and return DTOs only.
 */
final class PublishedDescriptionRepository implements PublishedDescriptionRepositoryInterface
{
    public function __construct(private readonly Connection $db)
    {
    }

    public function all(): array
    {
        $rows = $this->db
            ->createCommand(
                'SELECT id, description, published_from, published_to'
                . ' FROM {{%publications_description}} ORDER BY published_from DESC, id DESC',
            )
            ->queryAll(PDO::FETCH_ASSOC);

        return array_map(
            static fn (array $row): PublishedDescriptionData => new PublishedDescriptionData(
                (int)$row['id'],
                (string)$row['description'],
                (string)$row['published_from'],
                $row['published_to'] === null ? null : (string)$row['published_to'],
            ),
            $rows,
        );
    }

    public function archiveAndStart(string $description, string $now): void
    {
        $transaction = $this->db->beginTransaction();
        try {
            $this->db
                ->createCommand(
                    'UPDATE {{%publications_description}} SET published_to = :now'
                    . ' WHERE published_to IS NULL',
                )
                ->bindValue(':now', $now)
                ->execute();
            $this->db
                ->createCommand()
                ->insert('{{%publications_description}}', [
                    'description' => $description,
                    'published_from' => $now,
                    'published_to' => null,
                ])
                ->execute();
            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }
}
