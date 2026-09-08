#!/bin/sh
set -eu

schedule="${PARSER_CRON_SCHEDULE:-0 4 * * *}"
post_schedule="${FORUM_POST_PARSER_CRON_SCHEDULE:-*/10 * * * *}"
gallery_schedule="${GALLERY_PARSER_CRON_SCHEDULE:-*/10 * * * *}"
member_schedule="${MEMBER_PARSER_CRON_SCHEDULE:-*/10 * * * *}"
crontab_file="/tmp/parser-crontab"

printf '%s php /var/www/html/yii forum-parser/scan\n' "$schedule" > "$crontab_file"
printf '%s php /var/www/html/yii forum-post-parser/scan\n' "$post_schedule" >> "$crontab_file"
printf '%s php /var/www/html/yii gallery-parser/scan\n' "$gallery_schedule" >> "$crontab_file"
printf '%s php /var/www/html/yii member-parser/scan\n' "$member_schedule" >> "$crontab_file"

exec supercronic -passthrough-logs "$crontab_file"
