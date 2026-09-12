#!/bin/sh
set -eu

schedule="${PARSER_CRON_SCHEDULE:-0 4 * * *}"
post_schedule="${FORUM_POST_PARSER_CRON_SCHEDULE:-*/10 * * * *}"
gallery_schedule="${GALLERY_PARSER_CRON_SCHEDULE:-*/10 * * * *}"
member_schedule="${MEMBER_PARSER_CRON_SCHEDULE:-*/10 * * * *}"
publish_schedule="${TELEGRAM_PUBLISH_CRON_SCHEDULE:-*/5 * * * *}"
delete_schedule="${TELEGRAM_DELETE_CRON_SCHEDULE:-*/5 * * * *}"
crontab_file="/tmp/parser-crontab"

printf '%s php /var/www/html/yii forum-parser/scan\n' "$schedule" > "$crontab_file"
printf '%s php /var/www/html/yii forum-post-parser/scan\n' "$post_schedule" >> "$crontab_file"
printf '%s php /var/www/html/yii gallery-parser/scan\n' "$gallery_schedule" >> "$crontab_file"
printf '%s php /var/www/html/yii member-parser/scan\n' "$member_schedule" >> "$crontab_file"
printf '%s php /var/www/html/yii telegram/publish-due\n' "$publish_schedule" >> "$crontab_file"
printf '%s php /var/www/html/yii telegram/delete-due\n' "$delete_schedule" >> "$crontab_file"

exec supercronic -passthrough-logs "$crontab_file"
