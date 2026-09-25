<?php

declare(strict_types=1);

namespace app\shared\Settings\Infrastructure;

use app\shared\Settings\Contract\PublicationSettingsRepositoryInterface;
use PDO;
use yii\db\Connection;

/**
 * PostgreSQL storage for the publication settings. All SQL lives here: the
 * upper layers receive and return plain code => value maps only.
 */
final class PublicationSettingsRepository implements PublicationSettingsRepositoryInterface
{
    public function __construct(private readonly Connection $db)
    {
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        $rows = $this->db
            ->createCommand('SELECT code, value FROM {{%publications_setting}} ORDER BY code')
            ->queryAll(PDO::FETCH_ASSOC);

        $values = [];
        foreach ($rows as $row) {
            $values[(string)$row['code']] = (string)$row['value'];
        }

        return $values;
    }

    /**
     * @param array<string, string> $values
     */
    public function saveMany(array $values, string $now): void
    {
        if ($values === []) {
            return;
        }

        $sql = 'INSERT INTO {{%publications_setting}} (code, value, updated_at)'
            . ' VALUES (:code, :value, :now)'
            . ' ON CONFLICT (code) DO UPDATE SET value = EXCLUDED.value, updated_at = EXCLUDED.updated_at';

        $transaction = $this->db->beginTransaction();
        try {
            foreach ($values as $code => $value) {
                $this->db->createCommand($sql)->bindValues([
                    ':code' => $code,
                    ':value' => $value,
                    ':now' => $now,
                ])->execute();
            }
            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }
}
