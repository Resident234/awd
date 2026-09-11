<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Creates the publications_edited table: an edit-history archive for
 * channel posts. Same columns as publications_post plus edited_at.
 */
final class m260911_000010_create_publications_edited extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%publications_edited}}', [
            'id' => $this->primaryKey(),
            'telegram_id' => $this->bigInteger()->null(),
            'text' => $this->text()->notNull(),
            'image_urls' => $this->json()->notNull()->defaultValue('[]'),
            'published_at' => $this->dateTime()->notNull(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
            'edited_at' => $this->dateTime()->null(),
        ]);
        $this->createIndex('idx_publications_edited_edited_at', '{{%publications_edited}}', 'edited_at');
    }

    public function safeDown(): void
    {
        $this->dropIndex('idx_publications_edited_edited_at', '{{%publications_edited}}');
        $this->dropTable('{{%publications_edited}}');
    }
}
