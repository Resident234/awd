<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Adds the image_urls jsonb column to the post table: the list of
 * image URLs collected from each post content block, same format
 * as topic.image_urls.
 */
final class m260913_000014_add_image_urls_to_post extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%post}}', 'image_urls', $this->json()->notNull()->defaultValue('[]'));
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%post}}', 'image_urls');
    }
}
