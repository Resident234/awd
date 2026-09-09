<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Creates the published_descriptions table: the archive of TRVL channel
 * descriptions. The currently active row has published_to = NULL.
 */
final class m260909_000006_create_published_descriptions extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%published_description}}', [
            'id' => $this->primaryKey(),
            'description' => $this->string(255)->notNull()->defaultValue(''),
            'published_from' => $this->dateTime()->notNull(),
            'published_to' => $this->dateTime()->null(),
        ]);
        $this->createIndex('idx_published_description_published_to', '{{%published_description}}', 'published_to');
    }

    public function safeDown(): void
    {
        $this->dropIndex('idx_published_description_published_to', '{{%published_description}}');
        $this->dropTable('{{%published_description}}');
    }
}
