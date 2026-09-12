<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Makes publications_deleted.deleted_at nullable: an empty deleted_at
 * marks a soft-deleted record that still has to be removed from the
 * channel; the periodic telegram/delete-due task stamps it with the
 * actual removal time.
 */
final class m260912_000011_make_publications_deleted_at_nullable extends Migration
{
    public function safeUp(): void
    {
        $this->alterColumn('{{%publications_deleted}}', 'deleted_at', $this->dateTime()->null());
    }

    public function safeDown(): void
    {
        $this->alterColumn('{{%publications_deleted}}', 'deleted_at', $this->dateTime()->notNull());
    }
}
