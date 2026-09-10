<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Creates the publications data model: publications_draft stores drafts
 * before they are sent to Telegram, publications_post stores channel
 * posts including scheduled ones (published_at in the future).
 */
final class m260910_000008_create_publications_tables extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%publications_draft}}', [
            'id' => $this->primaryKey(),
            'text' => $this->text()->notNull(),
            'image_urls' => $this->json()->notNull()->defaultValue('[]'),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);

        $this->createTable('{{%publications_post}}', [
            'id' => $this->primaryKey(),
            'telegram_id' => $this->bigInteger()->null(),
            'text' => $this->text()->notNull(),
            'image_urls' => $this->json()->notNull()->defaultValue('[]'),
            'published_at' => $this->dateTime()->notNull(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);
        $this->createIndex('idx_publications_post_telegram_id', '{{%publications_post}}', 'telegram_id', true);
        $this->createIndex('idx_publications_post_published_at', '{{%publications_post}}', 'published_at');
    }

    public function safeDown(): void
    {
        $this->dropIndex('idx_publications_post_published_at', '{{%publications_post}}');
        $this->dropIndex('idx_publications_post_telegram_id', '{{%publications_post}}');
        $this->dropTable('{{%publications_post}}');
        $this->dropTable('{{%publications_draft}}');
    }
}
