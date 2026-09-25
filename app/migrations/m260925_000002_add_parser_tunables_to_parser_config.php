<?php

declare(strict_types=1);

use app\shared\Forum\Service\ParserSettingsService;
use yii\db\Migration;

/**
 * Moves the tunables of the parsers out of the code and into parser_config:
 * how long one request waits, how often it is repeated, how many pages a
 * scan walks, which address it logs in through and which timezone the site
 * speaks. Every row of the table carries the same values for these columns,
 * because they belong to the whole scan and not to one entity range; the
 * scan services read them from the row they already load. PostgreSQL gives
 * the rows that already exist the default of the new column, so no backfill
 * runs here.
 */
final class m260925_000002_add_parser_tunables_to_parser_config extends Migration
{
    public function safeUp(): void
    {
        $defaults = ParserSettingsService::DEFAULTS;

        $this->addColumn('{{%parser_config}}', 'http_timeout', $this->integer()->notNull()->defaultValue($defaults['http_timeout']));
        $this->addColumn('{{%parser_config}}', 'http_retries', $this->integer()->notNull()->defaultValue($defaults['http_retries']));
        $this->addColumn('{{%parser_config}}', 'http_delay_microseconds', $this->integer()->notNull()->defaultValue($defaults['http_delay_microseconds']));
        $this->addColumn('{{%parser_config}}', 'http_max_redirects', $this->integer()->notNull()->defaultValue($defaults['http_max_redirects']));
        $this->addColumn('{{%parser_config}}', 'http_banned_statuses', $this->string(64)->notNull()->defaultValue($defaults['http_banned_statuses']));
        $this->addColumn('{{%parser_config}}', 'login_url', $this->string(500)->notNull()->defaultValue($defaults['login_url']));
        $this->addColumn('{{%parser_config}}', 'max_pages', $this->integer()->notNull()->defaultValue($defaults['max_pages']));
        $this->addColumn('{{%parser_config}}', 'source_timezone', $this->string(64)->notNull()->defaultValue($defaults['source_timezone']));
    }

    public function safeDown(): void
    {
        foreach (array_keys(ParserSettingsService::DEFAULTS) as $column) {
            $this->dropColumn('{{%parser_config}}', $column);
        }
    }
}
