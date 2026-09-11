<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Creates the publications_deleted table: a soft-delete archive for
 * channel posts. Same columns as publications_post plus deleted_at.
 */
final class m260911_000009_create_publications_deleted extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%publications_deleted}}', [
            'id' => $this->primaryKey(),
            'telegram_id' => $this->bigInteger()->null(),
            'text' => $this->text()->notNull(),
            'image_urls' => $this->json()->notNull()->defaultValue('[]'),
            'published_at' => $this->dateTime()->notNull(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
            'deleted_at' => $this->dateTime()->notNull(),
        ]);
        $this->createIndex('idx_publications_deleted_deleted_at', '{{%publications_deleted}}', 'deleted_at');
    }

    public function safeDown(): void
    {
        $this->dropIndex('idx_publications_deleted_deleted_at', '{{%publications_deleted}}');
        $this->dropTable('{{%publications_deleted}}');
    }
}
