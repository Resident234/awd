<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Creates the forum parser data model: parser_config, member, topic.
 */
final class m260828_000001_create_forum_parser_tables extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%parser_config}}', [
            'id' => $this->primaryKey(),
            'code' => $this->string(100)->notNull(),
            'base_url' => $this->string(500)->notNull(),
            't_from' => $this->integer()->notNull()->defaultValue(0),
            't_to' => $this->integer()->notNull()->defaultValue(500000),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'last_run_at' => $this->dateTime()->null(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
        ]);
        $this->createIndex('uq_parser_config_code', '{{%parser_config}}', 'code', true);

        $this->createTable('{{%member}}', [
            'id' => $this->integer()->notNull(),
            'profile_url' => $this->string(1000)->notNull(),
            'name' => $this->string(255)->notNull(),
            'avatar_url' => $this->string(1000)->null(),
            'rank_name' => $this->string(255)->null(),
            'messages_count' => $this->integer()->null(),
            'registered_on' => $this->date()->null(),
            'city' => $this->string(255)->null(),
            'thanks_given_count' => $this->integer()->null(),
            'thanks_received_count' => $this->integer()->null(),
            'age' => $this->integer()->null(),
            'countries_count' => $this->integer()->null(),
            'reports_count' => $this->integer()->null(),
            'gender' => $this->string(100)->null(),
            'raw_data' => $this->json()->notNull()->defaultValue('{}'),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
            'PRIMARY KEY ([[id]])',
        ]);

        $this->createTable('{{%topic}}', [
            'id' => $this->integer()->notNull(),
            'source_url' => $this->string(1000)->notNull(),
            'title' => $this->string(1000)->notNull(),
            'published_at' => $this->dateTime()->null(),
            'content_html' => $this->text()->notNull(),
            'content_text' => $this->text()->notNull(),
            'image_urls' => $this->json()->notNull()->defaultValue('[]'),
            'author_id' => $this->integer()->null(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
            'PRIMARY KEY ([[id]])',
        ]);
        $this->addForeignKey(
            'fk_topic_author',
            '{{%topic}}',
            'author_id',
            '{{%member}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $now = gmdate('Y-m-d H:i:s');
        $this->insert('{{%parser_config}}', [
            'code' => 'awd_forum_topics',
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
        $this->dropForeignKey('fk_topic_author', '{{%topic}}');
        $this->dropTable('{{%topic}}');
        $this->dropTable('{{%member}}');
        $this->dropTable('{{%parser_config}}');
    }
}
