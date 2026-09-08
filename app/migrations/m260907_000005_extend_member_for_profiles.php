<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Extends the member table for profile page parsing and seeds
 * the awd_forum_members parser config.
 */
final class m260907_000005_extend_member_for_profiles extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%member}}', 'last_visit_at', $this->dateTime()->null()->after('registered_on'));
        $this->addColumn('{{%member}}', 'photos_count', $this->integer()->null()->after('messages_count'));
        $this->addColumn('{{%member}}', 'profile_login_required', $this->boolean()->notNull()->defaultValue(false)->after('gender'));

        $now = gmdate('Y-m-d H:i:s');
        $this->insert('{{%parser_config}}', [
            'code' => 'awd_forum_members',
            'base_url' => 'https://forum.awd.ru/memberlist.php?mode=viewprofile&u=',
            't_from' => 0,
            't_to' => 500000,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function safeDown(): void
    {
        $this->delete('{{%parser_config}}', ['code' => 'awd_forum_members']);
        $this->dropColumn('{{%member}}', 'profile_login_required');
        $this->dropColumn('{{%member}}', 'photos_count');
        $this->dropColumn('{{%member}}', 'last_visit_at');
    }
}
