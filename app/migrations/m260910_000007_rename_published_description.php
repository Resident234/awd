<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Renames the published_description table to publications_description.
 */
final class m260910_000007_rename_published_description extends Migration
{
    public function safeUp(): void
    {
        $this->renameTable('{{%published_description}}', '{{%publications_description}}');
        $this->dropIndex('idx_published_description_published_to', '{{%publications_description}}');
        $this->createIndex('idx_publications_description_published_to', '{{%publications_description}}', 'published_to');
    }

    public function safeDown(): void
    {
        $this->dropIndex('idx_publications_description_published_to', '{{%publications_description}}');
        $this->createIndex('idx_published_description_published_to', '{{%publications_description}}', 'published_to');
        $this->renameTable('{{%publications_description}}', '{{%published_description}}');
    }
}
