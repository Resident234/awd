<?php

declare(strict_types=1);

use app\shared\Settings\Service\PublicationSettingsService;
use yii\db\Migration;

/**
 * Creates the store of the publication settings: one row per tunable, keyed
 * by its code. The rows are seeded from the schema of the settings service,
 * so the defaults of the application and the defaults of the database are
 * written once.
 */
final class m260925_000001_create_publications_setting extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%publications_setting}}', [
            'code' => $this->string(64)->notNull(),
            'value' => $this->string(255)->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
            'PRIMARY KEY ([[code]])',
        ]);

        $now = gmdate('Y-m-d H:i:s');
        foreach (PublicationSettingsService::defaults() as $code => $value) {
            $this->insert('{{%publications_setting}}', [
                'code' => $code,
                'value' => $value,
                'updated_at' => $now,
            ]);
        }
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%publications_setting}}');
    }
}
