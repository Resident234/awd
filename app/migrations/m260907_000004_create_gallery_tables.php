<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Creates the gallery parser data model: gallery_album, gallery_image
 * and seeds the awd_gallery_albums parser config.
 */
final class m260907_000004_create_gallery_tables extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%gallery_album}}', [
            'id' => $this->integer()->notNull(),
            'source_url' => $this->string(1000)->notNull(),
            'title' => $this->string(1000)->notNull()->defaultValue(''),
            'username' => $this->string(255)->null(),
            'login_required' => $this->boolean()->notNull()->defaultValue(false),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
            'PRIMARY KEY ([[id]])',
        ]);

        $this->createTable('{{%gallery_image}}', [
            'id' => $this->bigInteger()->notNull(),
            'album_id' => $this->integer()->notNull(),
            'title' => $this->string(1000)->notNull()->defaultValue(''),
            'image_url' => $this->string(1000)->notNull(),
            'source_url' => $this->string(1000)->notNull(),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->notNull(),
            'PRIMARY KEY ([[id]])',
        ]);
        $this->createIndex('idx_gallery_image_album_id', '{{%gallery_image}}', 'album_id');
        $this->addForeignKey(
            'fk_gallery_image_album',
            '{{%gallery_image}}',
            'album_id',
            '{{%gallery_album}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $now = gmdate('Y-m-d H:i:s');
        $this->insert('{{%parser_config}}', [
            'code' => 'awd_gallery_albums',
            'base_url' => 'https://forum.awd.ru/gallery/album.php?album_id=',
            't_from' => 0,
            't_to' => 500000,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function safeDown(): void
    {
        $this->delete('{{%parser_config}}', ['code' => 'awd_gallery_albums']);
        $this->dropForeignKey('fk_gallery_image_album', '{{%gallery_image}}');
        $this->dropIndex('idx_gallery_image_album_id', '{{%gallery_image}}');
        $this->dropTable('{{%gallery_image}}');
        $this->dropTable('{{%gallery_album}}');
    }
}
