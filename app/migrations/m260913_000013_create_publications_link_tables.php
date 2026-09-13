<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Creates link tables between forum entities and Telegram messages:
 * publications_post_map maps forum post.id to a Telegram message id,
 * publications_topic_map maps forum topic.id to a Telegram message id.
 */
final class m260913_000013_create_publications_link_tables extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%publications_post_map}}', [
            'post_id' => $this->bigInteger()->notNull(),
            'telegram_id' => $this->bigInteger()->null(),
            'PRIMARY KEY ([[post_id]])',
        ]);
        $this->addForeignKey(
            'fk_publications_post_map_post',
            '{{%publications_post_map}}',
            'post_id',
            '{{%post}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->createIndex('idx_publications_post_map_telegram_id', '{{%publications_post_map}}', 'telegram_id', true);

        $this->createTable('{{%publications_topic_map}}', [
            'topic_id' => $this->integer()->notNull(),
            'telegram_id' => $this->bigInteger()->null(),
            'PRIMARY KEY ([[topic_id]])',
        ]);
        $this->addForeignKey(
            'fk_publications_topic_map_topic',
            '{{%publications_topic_map}}',
            'topic_id',
            '{{%topic}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->createIndex('idx_publications_topic_map_telegram_id', '{{%publications_topic_map}}', 'telegram_id', true);
    }

    public function safeDown(): void
    {
        $this->dropIndex('idx_publications_topic_map_telegram_id', '{{%publications_topic_map}}');
        $this->dropForeignKey('fk_publications_topic_map_topic', '{{%publications_topic_map}}');
        $this->dropTable('{{%publications_topic_map}}');

        $this->dropIndex('idx_publications_post_map_telegram_id', '{{%publications_post_map}}');
        $this->dropForeignKey('fk_publications_post_map_post', '{{%publications_post_map}}');
        $this->dropTable('{{%publications_post_map}}');
    }
}
