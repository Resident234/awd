<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Makes publications_deleted.published_at nullable: soft-deleted
 * drafts keep their NULL published_at when moved to the archive.
 */
final class m260912_000012_make_publications_deleted_published_at_nullable extends Migration
{
    public function safeUp(): void
    {
        $this->alterColumn('{{%publications_deleted}}', 'published_at', $this->dateTime()->null());
    }

    public function safeDown(): void
    {
        $this->alterColumn('{{%publications_deleted}}', 'published_at', $this->dateTime()->notNull());
    }
}
