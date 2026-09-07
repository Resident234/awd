# Парсер форума awd.ru

Описание реализации парсинга тем форума `forum.awd.ru` (phpBB) в PostgreSQL: модель данных, слои, обход диапазона, парсинг постов тем, периодический запуск.

## Модель данных

Миграции: `app/migrations/m260828_000001_create_forum_parser_tables.php`, `app/migrations/m260904_000002_add_topic_login_required.php`, `app/migrations/m260906_000003_create_post_table.php`, `app/migrations/m260907_000004_create_gallery_tables.php`

### parser_config

Конфигурации диапазона перебора парсера.

| Поле | Тип | Назначение |
|---|---|---|
| `id` | PK | — |
| `code` | string, UNIQUE | Ключ конфигурации |
| `base_url` | string(500) | База URL, `https://forum.awd.ru/viewtopic.php?t=` |
| `t_from` | int, default 0 | Начало диапазона параметра `t` |
| `t_to` | int, default 500000 | Конец диапазона параметра `t` |
| `is_active` | bool, default true | Активность конфигурации |
| `last_run_at` | datetime, NULL | Время последнего прохода |
| `created_at` / `updated_at` | datetime | Метки времени |

Миграция вставляет сид `awd_forum_topics` с диапазоном `0–500000`.

### topic

Тема форума. `id` = значение параметра `t` (например, `441019`).

| Поле | Тип | Назначение |
|---|---|---|
| `id` | PK | Параметр `t` |
| `source_url` | string(1000) | Полная ссылка `https://forum.awd.ru/viewtopic.php?t=441019` |
| `title` | string(1000) | Название темы |
| `published_at` | datetime, NULL | Нормализованная дата публикации `Y-m-d H:i:s` |
| `content_html` | text | Полный HTML содержимого |
| `content_text` | text | Текстовая версия содержимого |
| `image_urls` | jsonb | Массив ссылок на изображения |
| `author_id` | FK → member.id | Автор темы (`SET NULL` при удалении) |
| `login_required` | bool, default false | Тема в разделе только для авторизованных |
| `created_at` / `updated_at` | datetime | Метки времени |

Миграция: `m260904_000002_add_topic_login_required` добавляет столбец `login_required`.

**Login-required темы**: страницы с текстом «вы должны быть авторизованы» тоже сохраняются в `topic` — только `id` и `source_url`, все остальные поля пустые (`title` = `''`), `login_required = true`. Если тема позже становится доступной, обычный upsert обновляет запись полными данными и сбрасывает флаг. Заглушки не создают записей в `member`.

### member

Автор темы из блока `dl.postprofile`.

| Поле | Тип | Источник в профиле |
|---|---|---|
| `id` | PK | Параметр `u` ссылки профиля (`23071`) |
| `profile_url` | string(1000) | Полная ссылка профиля |
| `name` | string(255) | Имя (`Jo`) |
| `avatar_url` | string(1000), NULL | Ссылка на аватар |
| `rank_name` | string(255), NULL | Звание (`новичок`) |
| `messages_count` | int, NULL | Сообщения: 37 |
| `registered_on` | date, NULL | Регистрация: 10.07.2007 → `2007-07-10` |
| `city` | string(255), NULL | Город: Москва |
| `thanks_given_count` | int, NULL | Благодарил (а): 0 |
| `thanks_received_count` | int, NULL | Поблагодарили: 34 |
| `age` | int, NULL | Возраст: 50 |
| `countries_count` | int, NULL | Страны: 26 |
| `reports_count` | int, NULL | Отчеты: 9 |
| `gender` | string(100), NULL | Пол: Мужской |
| `raw_data` | jsonb | Сырые поля профиля (для будущих доработок) |

Замечание по схеме: уникальный индекс на `topic.source_url` отсутствует намеренно — Yii pgsql upsert строит `ON CONFLICT` из всех unique-ограничений таблицы, и составной конфликт `(id, source_url)` без соответствующего constraint ломает запрос. `id` уже уникален и детерминированно порождает `source_url`.

### post

Пост темы. `id` = значение параметра `p` (например, `p11861699` → `11861699`).

| Поле | Тип | Назначение |
|---|---|---|
| `id` | bigint, PK | Параметр `p` |
| `topic_id` | int, FK → topic.id | Ссылка на топик (`CASCADE`) |
| `author_id` | int, FK → member.id, NULL | Автор поста (`SET NULL`) |
| `number` | int, NULL | Номер поста в теме из блока «Сообщение: #4495» |
| `title` | string(1000) | Заголовок поста из `h3` |
| `posted_at` | datetime, NULL | Дата поста из `p.author` |
| `content_html` | text | HTML содержимого `div.content` |
| `content_text` | text | Текстовая версия содержимого |
| `source_url` | string(1000) | `https://forum.awd.ru/viewtopic.php?p=11861699#p11861699` |
| `created_at` / `updated_at` | datetime | Метки времени |

Индексы: `idx_post_topic_id`, `idx_post_author_id`. FK `fk_post_topic` → `topic.id`, `fk_post_author` → `member.id`.

### parser_config для постов

Миграция `m260906_000003` вставляет сид `awd_forum_posts` с тем же диапазоном `0–500000`; диапазон используется для выборки существующих топиков через `existingTopicIds()` (login-required заглушки пропускаются).

### gallery_album

Альбом галереи. `id` = значение параметра `album_id` (например, `54903`).

| Поле | Тип | Назначение |
|---|---|---|
| `id` | PK | Параметр `album_id` |
| `source_url` | string(1000) | Полная ссылка `https://forum.awd.ru/gallery/album.php?album_id=54903` |
| `title` | string(1000) | Название из `h2 > a` (`Metallica`) |
| `username` | string(255), NULL | Имя пользователя — 4-й элемент навигации личных альбомов (`8008`); у общих альбомов NULL |
| `login_required` | bool, default false | Альбом доступен только авторизованным (заглушка) |
| `created_at` / `updated_at` | datetime | Метки времени |

### gallery_image

Изображение альбома. `id` = значение параметра `image_id` (например, `2048754`).

| Поле | Тип | Назначение |
|---|---|---|
| `id` | bigint, PK | Параметр `image_id` из ссылки `image_page.php` |
| `album_id` | int, FK → gallery_album.id | Привязка к альбому (`CASCADE`) |
| `title` | string(1000) | Название изображения (`Met 23-18`) |
| `image_url` | string(1000) | Ссылка на полноразмерное изображение `https://forum.awd.ru/gallery/images/upload/c0e/423/....jpg` |
| `source_url` | string(1000) | Ссылка на страницу изображения `image_page.php?album_id=...&image_id=...` |
| `created_at` / `updated_at` | datetime | Метки времени |

Индексы: `idx_gallery_image_album_id`. FK `fk_gallery_image_album` → `gallery_album.id`.

### parser_config для галереи

Миграция `m260907_000004` вставляет сид `awd_gallery_albums` с диапазоном `0–500000`, `base_url` `https://forum.awd.ru/gallery/album.php?album_id=`.

## Слои (по README)

```
app/
├── commands/ForumParserController.php          # Application: тонкая команда (темы)
├── commands/ForumPostParserController.php      # Application: тонкая команда (посты)
├── commands/GalleryParserController.php        # Application: тонкая команда (альбомы галереи)
├── config/console.php                          # composition root
├── migrations/m260828_000001_...php            # модель данных (parser_config, member, topic)
├── migrations/m260906_000003_...php            # таблица post + сид awd_forum_posts
├── migrations/m260907_000004_...php            # таблицы gallery_album, gallery_image + сид awd_gallery_albums
├── shared/Forum/                               # Shared-модуль
│   ├── Contract/
│   │   ├── ForumHttpClientInterface.php        # граница HTTP
│   │   └── ForumRepositoryInterface.php        # граница хранения (upsert тем, постов, lock)
│   ├── Dto/
│   │   ├── TopicData.php
│   │   ├── PostData.php
│   │   └── MemberData.php
│   ├── Infrastructure/
│   │   ├── ForumHttpClient.php                # cURL-адаптер
│   │   ├── ForumPageNotFoundException.php      # 404
│   │   ├── ForumLoginRequiredException.php     # раздел для авторизованных
│   │   ├── ForumRepository.php                 # SQL (PostgreSQL)
│   │   └── YiiPsrLoggerAdapter.php            # PSR-3 над yii-логгером
│   └── Service/
│       ├── ForumPageDomParser.php              # базовые хелперы DOM/даты/URL
│       ├── ForumHtmlParser.php                 # DOMDocument/XPath (тема)
│       ├── ForumPostPageParser.php            # DOMDocument/XPath (посты + пагинация)
│       ├── ForumScanService.php                # обход диапазона тем
│       └── ForumPostScanService.php            # обход топиков и страниц постов
└── shared/Gallery/                             # Shared-модуль галереи
    ├── Contract/
    │   └── GalleryRepositoryInterface.php       # граница хранения (upsert альбомов, lock)
    ├── Dto/
    │   ├── AlbumData.php
    │   └── GalleryImageData.php
    ├── Infrastructure/
    │   └── GalleryRepository.php              # SQL (PostgreSQL)
    └── Service/
        ├── GalleryAlbumPageParser.php          # DOMDocument/XPath (альбом + пагинация)
        └── GalleryScanService.php              # обход диапазона альбомов
```

- **Application**: команды `yii forum-parser/scan`, `yii forum-post-parser/scan` и `yii gallery-parser/scan` принимают `--from`, `--to`, `--limit` (посты и галерея: ещё `--pageLimit`), выводят статистику. Логики парсинга не содержат.
- **Shared**: сервисы, парсеры и DTO. SQL и HTTP скрыты за контрактами; замена хранилища или HTTP-адаптера не требует изменений в командах и сервисах.
- **Composition root**: `config/console.php` связывает реализации через `controllerMap` и `container.definitions`.

## Парсинг страниц

`ForumHtmlParser` построен на реальной структуре страниц phpBB (prosilver) форума:

- **Заголовок**: `h2.topic-title` (fallback: `h3.first`, `h1`)
- **Дата**: текст `p.author`, форматы `27 авг 2026, 19:42`, `Вчера, 19:42`, `Сегодня, 09:05`, `дд.мм.гггг, чч:мм` → `Y-m-d H:i:s` (часовой пояс сайта Europe/Moscow, хранение в UTC). Относительные слова («Вчера», «Сегодня») вычисляются относительно времени загрузки страницы и в БД не пишутся.
- **Содержимое**: `div.content` внутри `div.post` с `dl.postprofile`; сохраняется HTML и текстовая версия (`<br>` → перевод строки)
- **Изображения**: атрибут `data-src` (lazyload), fallback `src`; фильтр по расширениям (gif/jpg/png/webp); дедупликация; результат — массив абсолютных ссылок вида `https://live.staticflickr.com/65535/55491522951_2a109d663b_b.jpg`
- **Автор**: блок `dl.postprofile`; имя — ссылка в `dt` без `img` (первая ссылка — аватар), поля — по меткам `strong` в `dd`, звание — первый `dd` без `strong`
- **Нормализация URL**: из всех извлекаемых ссылок (`profile_url`, `avatar_url`, URL в полях профиля) удаляется сессионный параметр phpBB `sid` — `...?mode=viewprofile&u=797583&sid=1c5e...` → `...?mode=viewprofile&u=797583`. Логика в `ForumHtmlParser::stripSessionId()` (вызывается из `absoluteUrl()`): параметр убирается из query-строки, остальные параметры и fragment сохраняются. Существующие записи `member` были очищены разовым UPDATE (`regexp_replace`); повторный проход тем обновляет URL автоматически.

Особые случаи распознаются отдельными исключениями и не считаются ошибками прохода:

- `ForumPageNotFoundException` — тема не существует (HTTP 404)
- `ForumLoginRequiredException` — раздел доступен только авторизованным («вы должны быть авторизованы»); страница сохраняется в `topic` как заглушка: только `id` и `source_url`, `login_required = true` (см. модель `topic`)

## Парсинг постов тем

`ForumPostScanService::run(from, to, limit, pageLimit)` обходит топики, уже сохранённые в таблице `topic` (login-required заглушки пропускаются), и для каждого проходит все страницы через пагинацию:

1. Захватывает advisory lock по коду конфига `awd_forum_posts` (отдельный от lock'а сканера тем — работают параллельно)
2. Читает активный `parser_config`; `from`/`to` сужают диапазон, но не расширяют
3. Для каждого топика: GET первой страницы `viewtopic.php?t=<id>`, парсинг постов, сохранение, переход к следующей странице
4. **Пагинация**: ссылки `viewtopic.php?t=...&start=N` из `div.pagination`; следующая страница = ссылка с минимальным `start` больше текущего; на последней странице таких ссылок нет — обход завершается
5. **Посты**: `div` с `id="p123456"` и `class="post"` (блок «Похожие темы» без `dl.postprofile` отфильтровывается). Для каждого поста извлекаются: заголовок (`h3/a`), номер («Сообщение: #4495»), дата (`p.author`), HTML и текст (`div.content`), автор (`dl.postprofile` — те же поля, что у автора темы). Автор добавляется/обновляется в `member`
6. **Дедупликация**: phpBB повторяет открывающий пост топика в начале каждой страницы с позиционным номером (`start+1`) — уже сохранённые в рамках топика post id пропускаются, чтобы не затирать корректный `number`
7. `savePosts()` пишет посты и авторов одной транзакцией на страницу; повторный проход обновляет существующие записи (upsert)
8. Ошибка одного топика не останавливает проход; статистика: `processed`, `pages`, `posts_saved`, `posts_updated`, `topics_failed`, `topics_not_found`, `topics_login_required`, `topics_skipped_no_posts`

Команда: `yii forum-post-parser/scan [--from=...] [--to=...] [--limit=N] [--pageLimit=N]`. Пример: полный проход топика 415949 — 189 страниц, 9447 постов, 1401 уникальный автор, повторный проход — 0 saved / 9447 updated.

## Парсинг альбомов галереи

`GalleryScanService::run(from, to, limit, pageLimit)` перебирает диапазон `album_id` (конфиг `awd_gallery_albums`, 0–500000) и для каждого альбома проходит все страницы через пагинацию:

1. Захватывает advisory lock по коду конфига `awd_gallery_albums` (отдельный от lock'ов сканера тем и постов — все три парсера работают параллельно)
2. Читает активный `parser_config`; `from`/`to` сужают диапазон, но не расширяют
3. Для каждого `album_id`: GET `album.php?album_id=<id>`, парсинг, обход страниц, сохранение
4. **Пагинация**: ссылки `album.php?album_id=...&start=N` из `div.pagination` (100 изображений на страницу); следующая страница = ссылка с минимальным `start` больше текущего; на последней странице таких ссылок нет
5. **Альбом**: название из `h2 > a` (`Metallica`); имя пользователя — 4-й элемент навигации `ul.linklist.navlinks` (`Список форумов › Галерея › Личные альбомы › 8008 › Metallica`), распознаётся по 3-й ссылке `mode=personal`; у общих альбомов 3-й элемент другой — `username = NULL`. Корневые личные альбомы пользователя (54902) содержат только подальбомы и сохраняются с названием и `username`, без изображений
6. **Изображения**: элементы `a.highslide`; из `href` — ссылка на полноразмерное изображение (`../gallery/images/upload/c0e/423/....jpg` → абсолютная), из `title` — название (`Met 23-18`), из экранированного `rel` — `image_id` (`image_page.php?...&image_id=2048754`). Дедупликация по `image_id` в рамках альбома; страницы мержатся в один набор
7. `save()` пишет альбом и все изображения одной транзакцией; повторный проход обновляет записи (upsert)
8. Ошибка одного альбома не останавливает проход; особые случаи: `Запрошенный альбом не существует` (HTTP 200 с текстом) → `ForumPageNotFoundException` → счётчик `not_found`; «вы должны быть авторизованы» → заглушка в `gallery_album` (`login_required = true`); битый HTML без `h2` → `failed`
9. Статистика: `processed`, `saved`, `updated`, `not_found`, `login_required`, `images_saved`, `images_updated`, `failed`

Команда: `yii gallery-parser/scan [--from=...] [--to=...] [--limit=N] [--pageLimit=N]`. Пример: альбом 54903 (Metallica) — 4 изображения; альбом 11186 — 16 страниц по 100 изображений.

Cron-контейнер запускает все три парсера по своим расписаниям:

```
PARSER_CRON_SCHEDULE=*/10 * * * *                  # forum-parser/scan (темы)
FORUM_POST_PARSER_CRON_SCHEDULE=*/10 * * * *      # forum-post-parser/scan (посты)
GALLERY_PARSER_CRON_SCHEDULE=*/10 * * * *         # gallery-parser/scan (альбомы)
```

## Обход и сохранение

`ForumScanService::run(from, to, limit)`:

1. Пытается захватить блокировку запуска (см. «Защита от параллельного запуска»); при неудаче возвращает `null`, ничего не делает
2. Читает активный `parser_config` (коды конфигураций поддерживаются несколько)
3. Ограничивает диапазон: явные `from`/`to` сужают диапазон конфига, но не расширяют его
4. Для каждого `t` выполняет GET, парсит и делает upsert топика и upsert автора в одной транзакции
5. Существующие записи обновляются; `save()` возвращает признак новой записи для статистики
6. Ошибка одной ссылки (404, таймаут, битый HTML) не останавливает проход — логируется и учитывается в счётчиках; login-required — не ошибка: сохраняется заглушка и инкрементируется `login_required` (запись также идёт в `saved`/`updated`)
7. По завершении обновляет `parser_config.last_run_at` и снимает блокировку

HTTP-адаптер (cURL): редиректы до 5, retry с нарастающей задержкой на 429/5xx, таймауты, User-Agent.

Статистика прохода: `processed`, `saved`, `updated`, `not_found`, `login_required`, `failed`.

Логирование — PSR-3 через `YiiPsrLoggerAdapter` (категория `forum-parser`), записи попадают в `app/runtime/logs/app.log`.

## Защита от параллельного запуска

Проход диапазона длительный, а запуск периодический (cron) — поэтому перед запуском проверяется, не выполняется ли уже этот же процесс:

- **Механизм**: session-level advisory lock PostgreSQL — `pg_try_advisory_lock` с ключом = CRC32 кода конфигурации
- **Границы действия**: работает между разными процессами и контейнерами (cron-контейнер `parser` + ручные запуски в `app`) — обе точки используют общую БД
- **Отказоустойчивость**: лок привязан к сессии БД и снимается автоматически при завершении процесса или обрыве соединения — упавший процесс никогда не заблокирует следующий запуск
- **Контракт**: `ForumRepositoryInterface::acquireLock()/releaseLock()`; SQL скрыт в `ForumRepository`
- **Сервис**: `run()` возвращает `null` при удерживаемой блокировке, иначе выполняет проход и снимает лок в `finally`
- **Команда**: при пропуске выводит `Skipped: another forum scan is already running` (exit code OK — cron не считает пропуск ошибкой)

Проверено интеграционно на живой БД: параллельный запуск во время активного прохода пропущен, после завершения прохода следующий запуск успешен.

## Параллельная работа парсеров

Сканер тем (`forum-parser/scan`, конфиг `awd_forum_topics`), парсер постов (`forum-post-parser/scan`, конфиг `awd_forum_posts`) и парсер галереи (`gallery-parser/scan`, конфиг `awd_gallery_albums`) работают одновременно и не мешают друг другу:

- **Независимые локи**: ключ advisory lock вычисляется из кода конфигурации (`crc32`), у каждого парсера свой ключ. Лок защищает только от повторного запуска *того же* парсера, другой парсер не блокируется
- **Независимые cron-задачи**: supercronic запускает все три прохода по своим расписаниям (`PARSER_CRON_SCHEDULE`, `FORUM_POST_PARSER_CRON_SCHEDULE`, `GALLERY_PARSER_CRON_SCHEDULE`)
- **Безопасная конкуренция за данные**: парсеры пишут в свои таблицы короткими транзакциями (один топик / одна страница постов / один альбом) через upsert. Редкие коллизии на одной строке PostgreSQL разрешает на уровне строк — вторая транзакция кратко ждёт первую
- **Логическая связка**: парсер постов берёт только топики, уже существующие в `topic`, поэтому он двигается по диапазону вслед за сканером тем; парсер галереи независим — работает только с таблицами `gallery_album` / `gallery_image`

Проверено на живой БД: оба процесса работали одновременно (каждый держал свой advisory lock в отдельной сессии), за 90 секунд пост-парсер добавил ~2700 постов, сканер тем параллельно обновлял топики.

## Наблюдаемость прогресса

`updated_at` у записей пишется **на каждую тему / каждую страницу постов / каждый альбом**, а не временем старта прохода — иначе все строки прохода получали одну метку и прогресс был не виден (`updated_at` застыл на времени запуска). Текущую позицию сканера тем можно оценить так:

```sql
SELECT max(id) FROM topic WHERE updated_at > now() - interval '2 minutes';
```

Для галереи — аналогично по `gallery_album`.

Замечание: сканеры тем и галереи перебирают все id диапазона подряд, включая несуществующие (404), поэтому «сколько осталось» — это доля пройденного диапазона, а не доля существующих элементов.

## Периодический запуск

Отдельный cron-контейнер в Docker Compose:

- `docker/php-cli/Dockerfile` — PHP 8.4-cli, расширения intl/mbstring/pdo_pgsql/zip, supercronic v0.2.29
- `docker/php-cli/entrypoint.sh` — генерирует crontab из переменной окружения и запускает supercronic
- `docker-compose.yml`, сервис `parser`: тот же volume `./app`, тот же `DB_*`, зависит от healthcheck postgres

Расписание задаётся в `.env`:

```
PARSER_CRON_SCHEDULE=*/10 * * * *
FORUM_POST_PARSER_CRON_SCHEDULE=*/10 * * * *
GALLERY_PARSER_CRON_SCHEDULE=*/10 * * * *
```

Запуск вручную:

```
docker compose exec app php yii forum-parser/scan --from=441000 --to=441025
docker compose exec app php yii forum-post-parser/scan --from=415949 --to=415949
docker compose exec app php yii gallery-parser/scan --from=54900 --to=54910
```

## Логи

Парсер пишет логи в два места:

**1. stdout контейнера `parser`** — запуски по расписанию (supercronic проксирует вывод команды):

```bash
# все выводы cron-запусков, в реальном времени
docker compose logs -f parser

# последние 50 строк
docker compose logs --tail 50 parser
```

**2. Файл `app/runtime/logs/app.log`** — подробные warning/info-записи Yii (категория `forum-parser`). Каталог `runtime` общий с контейнером через volume:

```bash
# хвост лога в реальном времени
docker compose exec app tail -f runtime/logs/app.log

# только события парсера
docker compose exec app sh -c "grep forum-parser runtime/logs/app.log | tail -20"
```

Файл доступен и с хоста: `H:\s\Work_awd\app\runtime\logs\app.log` (можно открыть в PhpStorm).

Формат записи: timestamp, уровень, категория `forum-parser`, сообщение и JSON-контекст (`topic_id`, `error`, счётчики финальной статистики `Forum scan finished`). Пропуск запуска из-за блокировки пишется как warning `Forum scan is already running, launch skipped.`

## Проверено

- Реальный проход диапазона 441000–441025: 26 обработано, 18 сохранено, 6 not found (404), 2 login required, 0 failed; повторный проход корректно обновляет записи (saved 0, updated 18)
- Тема 441019: заголовок, дата `2026-08-27 19:42:00`, 26 изображений, текст 8442 символа, автор 23071 со всеми полями профиля
- Защита от параллельного запуска: второй запуск во время активного прохода пропущен, лок снят после завершения
- Login-required темы 441013/441014: сохранены с `login_required = true`, пустым `title` и корректным `source_url`; повторный проход обновляет заглушки
- Парсер постов, тема 415949 (189 страниц): 9447 постов сохранено, 0 обновлено, 0 ошибок; непрерывная нумерация 1–9448, 1401 уникальный автор; повторный проход — 0 saved / 9447 updated (идемпотентно)
- Пост из примера `p11861699`/`p11860687`: заголовок, дата `2024-07-04 17:50:00`, номер #4495, автор 394702 (@nnet., все поля профиля) — совпадают с требованиями
- Парсер галереи, альбом 54903 (Metallica): id, название, username `8008` (4-й элемент навигации), 4 изображения с id (2048750–2048754), названиями и абсолютными ссылками — совпадают с требованиями; повторный проход — 0 saved / 1 updated / 4 images updated (идемпотентно)
- Парсер галереи, альбом 11186 (1563 фото): 16 страниц пагинации по 100 изображений, все 1563 сохранены одним набором
- Парсер галереи, диапазон 54900–54910: 11 обработано, 8 сохранено, 1 not found (текст «Запрошенный альбом не существует»), 76 изображений; корневые личные альбомы — с username, без изображений; общие — username NULL
- `vendor/bin/phpstan` — 0 ошибок
- `vendor/bin/phpcs` — 0 ошибок
- `vendor/bin/codecept run Unit` — 58 тестов зелёные, включая нормализацию «Вчера»/«Сегодня», статистику сервисов, блокировку, заглушки login-required, пагинацию, дедупликацию открывающего поста и все сценарии галереи

## Тесты

- `tests/Unit/shared/Forum/Service/ForumHtmlParserTest.php` — парсинг темы с автором и изображениями, относительные даты, невалидный HTML, удаление `sid` из ссылок профиля
- `tests/Unit/shared/Forum/Service/ForumPostPageParserTest.php` — парсинг всех постов страницы (заголовок, номер, дата, текст, автор), пагинация (`start=N`), последняя страница, блок «Похожие темы» отфильтровывается
- `tests/Unit/ForumScanServiceTest.php` — upsert-статистика, счётчики not found / login required / failed, сохранение login-required заглушки (id + url + флаг), лимит, отсутствие конфига, пропуск при удерживаемой блокировке
- `tests/Unit/ForumPostScanServiceTest.php` — обход всех страниц топика, обновление существующих постов, login-required, лимиты топиков и страниц, дедупликация повторяющегося открывающего поста, блокировка
- `tests/Unit/shared/Gallery/Service/GalleryAlbumPageParserTest.php` — парсинг альбома (название, username из навигации, изображения: id/название/ссылки), общие альбомы без username, несуществующий альбом (текст «Запрошенный альбом не существует»), пагинация (`start=N`), последняя страница
- `tests/Unit/GalleryScanServiceTest.php` — upsert-статистика альбомов и изображений, счётчики not found / login required / failed, заглушка login-required, обход пагинации альбома, лимит, отсутствие конфига, пропуск при удерживаемой блокировке

## Известные ограничения

- Темы закрытых разделов не парсятся полностью (нужна авторизация) — сохраняются заглушки с `login_required = true`
- Диапазоны 0–500000 тем и альбомов проходятся полностью при каждом запуске; оптимизация «пропускать неизменённые» — отдельная задача (см. TODO 12 в README)
- Парсер постов обходит только топики, уже существующие в таблице `topic` (кроме login-required заглушек), и двигается по диапазону вслед за сканером тем; все три парсера работают параллельно по независимым блокировкам (см. «Параллельная работа парсеров»)
- Альбомы закрытых разделов галереи могут требовать авторизацию — сохраняются заглушки; ссылка на изображение ведёт на файл в `images/upload` — при удалении изображения из галереи ссылка устареет до следующего прохода
