<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Creates the post table for topic post parsing and seeds
 * the awd_forum_posts parser config.
 */
final class m260906_000003_create_post_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%post}}', [
            'id' => $this->bigInteger()->notNull(),
            'topic_id' => $this->integer()->notNull(),
            'author_id' => $this->integer()->null(),
            'number' => $this->integer()->null(),
            'title' => $this->string(1000)->notNull()->defaultValue(''),
            'posted_at' => $this->dateTime()->null(),
            'content_html' => $this->text()->notNull(),
            'content_text' => $this->text()->notNull(),
            'source_url' => $this->string(1000)->notNull(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
            'PRIMARY KEY ([[id]])',
        ]);
        $this->createIndex('idx_post_topic_id', '{{%post}}', 'topic_id');
        $this->createIndex('idx_post_author_id', '{{%post}}', 'author_id');
        $this->addForeignKey(
            'fk_post_topic',
            '{{%post}}',
            'topic_id',
            '{{%topic}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_post_author',
            '{{%post}}',
            'author_id',
            '{{%member}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $now = gmdate('Y-m-d H:i:s');
        $this->insert('{{%parser_config}}', [
            'code' => 'awd_forum_posts',
            'base_url' => 'https://forum.awd.ru/viewtopic.php?t=',
            't_from' => 0,
            't_to' => 500000,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function safeDown(): void
    {
        $this->delete('{{%parser_config}}', ['code' => 'awd_forum_posts']);
        $this->dropForeignKey('fk_post_author', '{{%post}}');
        $this->dropForeignKey('fk_post_topic', '{{%post}}');
        $this->dropIndex('idx_post_author_id', '{{%post}}');
        $this->dropIndex('idx_post_topic_id', '{{%post}}');
        $this->dropTable('{{%post}}');
    }
}
