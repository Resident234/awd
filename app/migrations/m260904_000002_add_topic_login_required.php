<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Adds the login_required flag to topic for pages hidden behind authorization.
 */
final class m260904_000002_add_topic_login_required extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%topic}}', 'login_required', $this->boolean()->notNull()->defaultValue(false));
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%topic}}', 'login_required');
    }
}
