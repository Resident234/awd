# Publications and Telegram Posting

## Overview

This document provides a comprehensive description of the **publications** feature of the AWD project, covering:

- How publications are stored and managed (drafts, scheduled posts, published posts, soft-deleted posts).
- The workflow for creating, editing, deleting and publishing content.
- Integration with the **Telegram** channel (TRVL) - updating channel description, sending text or photo posts, pinning, editing and deleting messages.
- Console commands and cron schedules that automate publishing tasks.
- Configuration requirements.
- Detailed database schema and table relationships.
- Error handling and validation rules.

All of the above is implemented in the **Shared layer** of the project (see `README.md` for the layer diagram). The key classes are located under `app/shared/Publications` and `app/shared/Telegram`.

---

## Architecture

| Layer | Responsibility |
|------|-----------------|
| **Core** | Low-level technical abstractions, DTOs, exception types, utility helpers. |
| **Shared** | Business functionality - publications, forum integration, Telegram channel integration. |
| **Application** | UI controllers, console commands, configuration, orchestration of use-cases. |

The **Publications** module lives entirely in the **Shared** layer and does **not** depend on any Application code.

### Key Classes and Interfaces

#### Publications Module (`app/shared/Publications`)
- `PublicationsService` - Main service implementing use-cases for publications management
- `PublicationRepositoryInterface` - Abstract repository for publications persistence
- `PublicationForumLinkStoreInterface` - Temporary store for forum-publication links
- `PublicationData` - DTO representing a publication record
- `ForumPublicationRef` - DTO for forum entity references

#### Telegram Module (`app/shared/Telegram`)
- `ChannelService` - Service for Telegram channel operations (description, posting, editing, deleting)
- `TelegramChannelClientInterface` - Abstract client for Telegram Bot API communication
- `ChannelInfo` - DTO for channel information
- `PublishedDescriptionData` - DTO for published channel descriptions
- `PublishedDescriptionRepositoryInterface` - Repository for channel description history

#### Application Layer
- `TelegramController` (`app/commands/TelegramController.php`) - Console commands for Telegram operations
- `SiteController` (`app/controllers/SiteController.php`) - Web UI controllers for publications and channel management

---

## Domain Model

### Database Schema

The publications feature uses four tables to manage the lifecycle of content:

| Table | Purpose | Status Indicators |
|-------|---------|-------------------|
| `publications_post` | Main publications table | - `telegram_id IS NULL` = Scheduled (future `published_at`)<br>- `telegram_id IS NOT NULL` = Published<br>- `published_at` in past = Due for publishing |
| `publications_draft` | Draft publications | - Ready for editing/scheduling |
| `publications_edited` | Edit history of published posts | - `edited_at IS NULL` = Needs update in Telegram<br>- `edited_at IS NOT NULL` = Already updated in Telegram |
| `publications_deleted` | Soft-delete archive | - `deleted_at IS NULL` = Pending deletion from Telegram<br>- `deleted_at IS NOT NULL` = Already deleted from Telegram |

A publication whose text goes past the 4096-character limit of one Telegram message is stored as several `publications_post` (or `publications_draft`) rows, one per part: the rows are written by a single `createPosts()`/`createDrafts()` call inside a transaction, `published_at` puts each part a minute after the previous one, and there is no group column — the parts are ordinary records, ordered by `id`, and the «Часть N» heading is part of the text itself. Each row keeps its own `image_urls`, so a part is sent to the channel with the photos it holds: all of them on the first row by default, or spread over the rows when the form asks for an even distribution. Beside the caret split the form offers three manual modes for the text as a whole — by paragraphs, by lines, by sentences (`splitWholeText`, the buttons carrying `data-split-whole`): one part per boundary of the chosen kind, however short it comes out, with a piece too long for one message still cut down by `splitIntoParts`. See «Разбивка длинного текста на части» in [README.md](README.md).

Publications created from forum elements are additionally tracked in two link tables, one row per forum element:

| Table | Purpose | Status Indicators |
|-------|---------|-------------------|
| `publications_topic_map` | Links `topic.id` to a Telegram message | - no row = topic not seen<br>- `telegram_id IS NULL` = marked viewed («Просмотрено»)<br>- `telegram_id` set = published to the channel |
| `publications_post_map` | Links `post.id` to a Telegram message | - no row = post not seen<br>- `telegram_id IS NULL` = marked viewed («Просмотрено»)<br>- `telegram_id` set = published to the channel |

Both tables are written only through `ForumPublicationMapGatewayInterface` (see below). A topic that has exactly one post and that post count as a single element: marking or publishing either one writes the same state into the map row of the other, and a `telegram_id` the twin already has is never overwritten. Topics with several posts are not mirrored — one processed post says nothing about the rest.

The forum block filters («С изображениями», «С привязанными постами», «Кол-во изображений») and their session/URL sync are documented in [README.md](README.md), section «Фильтры блока «Форум»».

### Core DTOs and Interfaces

- **`PublicationData` (DTO)** - Represents a publication record with fields:
  - `id` (int): Primary key
  - `text` (string): Publication content (1-4096 characters, one record per part of a long publication)
  - `imageUrls` (string[]): Array of image URLs
  - `publishedAt` (string|null): Publication timestamp in UTC (`YYYY-MM-DD HH:MM:SS`), null for a draft
  - `createdAt` (string): Creation timestamp
  - `updatedAt` (string): Last update timestamp
  - `telegramId` (int|null): Telegram message ID (null until published)
  - `deletedAt` (string|null): Only for `publications_deleted` rows; null while the message still waits for removal from the channel

- **`PublicationRepositoryInterface`** - Abstract repository used by the service to persist publications (implemented by `PublicationRepository`, which keeps all the SQL):
  - `createDraft(string $text, array $imageUrls, string $now): int` - Creates a draft, returns its id
  - `createPost(string $text, array $imageUrls, string $publishedAt, string $now): int` - Creates a scheduled post, returns its id
  - `createPosts(array $parts, string $now): int` - Inserts the parts of one long publication as separate posts **in a single transaction** (all rows or none), returns the id of the first part
  - `createDrafts(array $parts, string $now): int` - Same, for drafts
  - `updateDraft(int $id, string $text, array $imageUrls, string $now): void` - Updates a draft, `created_at` untouched
  - `updatePost(int $id, string $text, array $imageUrls, string $publishedAt, string $now): void` - Updates a post, `created_at` untouched
  - `deletePost(int $id): PublicationData` / `deleteDraft(int $id): PublicationData` / `deleteDeleted(int $id): PublicationData` - Removes a row and returns it as it was before deletion
  - `findPost(int $id): PublicationData|null` - Finds a post by ID
  - `findDraft(int $id): PublicationData|null` - Finds a draft by ID
  - `findDeleted(int $id): PublicationData|null` - Finds a deleted record by ID
  - `allPosts(int $limit = 0, int $offset = 0, bool $oldestFirst = false): PublicationData[]` - Gets posts (scheduled and published), newest `published_at` first; `$limit` takes that many rows starting at `$offset`, `0` means the whole list, `$oldestFirst` reverses the order
  - `allDrafts(int $limit = 0, int $offset = 0, bool $oldestFirst = false): PublicationData[]` - Gets drafts by `updated_at`, paged and ordered the same way
  - `allDeleted(int $limit = 0, int $offset = 0, bool $oldestFirst = false): PublicationData[]` - Gets soft-deleted records by `updated_at`, paged and ordered the same way
  - `countPosts(): int` / `countDrafts(): int` / `countDeleted(): int` - How many rows each list holds without paging, so a paged reader knows when it has reached the end
  - `findDueForPublishing(string $now): PublicationData[]` - Posts with an empty `telegram_id` and `published_at <= now`, `id` ascending
  - `storeTelegramId(int $id, int $telegramId, string $publishedAt, string $now): void` - Stores the channel message id and corrects `published_at` to the send time
  - `insertPostWithHistory(PublicationData $post, string $now): int` / `insertDraftWithHistory(PublicationData $draft, string $now): int` / `insertDeletedWithHistory(PublicationData $record, string $now): int` - Insert a moved row preserving its original `created_at` (and `published_at` for archived records)
  - `archiveEdited(PublicationData $post, string $now): void` - Copies a published post into `publications_edited`
  - `findPendingChannelDeletion(): PublicationData[]` - Soft-deleted records still awaiting removal from the channel
  - `storeDeletedAt(int $id, string $deletedAt): void` - Stamps the time a record left the channel
  - `findPendingChannelEdits(): PublicationData[]` - Archived edits still awaiting the channel update
  - `storeEditedAt(int $id, string $editedAt): void` - Stamps the time a message was replaced in the channel

- **`ForumPublicationRef`** - Reference to the forum entity a publication was created from:
  - `type` (string): Either `'topic'` or `'post'`, `isTopic()` / `isPost()` test it
  - `id` (int): ID of the forum topic or post

- **`PublicationForumLinkStoreInterface`** - Temporary store that remembers the relation between a newly created draft/post and a forum entity until the publication is finally published:
  - `remember(int $publicationId, ForumPublicationRef $ref): void` - Stores the link
  - `find(int $publicationId): ForumPublicationRef|null` - Recalls the link
  - `move(int $fromPublicationId, int $toPublicationId): void` - Moves link between publication IDs
  - `forget(int $publicationId): void` - Removes the link

- **`ForumPublicationMapGatewayInterface`** - Permanent bridge tables storing the link between a forum entity and a Telegram message (implemented by `ForumRepository`):
  - `storeTopicMapTelegramId(int $topicId, ?int $telegramId): void` - Creates the `publications_topic_map` row with an empty `telegram_id`, or stamps an existing row with the channel message id
  - `storePostMapTelegramId(int $postId, ?int $telegramId): void` - Same for `publications_post_map`
  - Both mirror the state onto the twin entity when a topic has exactly one post — see the note under the link tables above

The concrete implementations live in the **Infrastructure** sub-namespace of each module and are wired via Yii DI container.

---

## Service API – `PublicationsService`

The service lives in `app/shared/Publications/Service/PublicationsService.php` and implements the following public use-cases:

| Method | Description |
|--------|-------------|
| `posts(int $limit = 0, int $offset = 0, bool $oldestFirst = false): array` | Returns *published* posts (including scheduled ones that are already due), newest `published_at` first. `$limit`/`$offset` read the list a page at a time, `0` returns all of it, `$oldestFirst` flips the order. |
| `drafts(int $limit = 0, int $offset = 0, bool $oldestFirst = false): array` | Returns drafts by `updated_at`, paged and ordered the same way. |
| `deleted(int $limit = 0, int $offset = 0, bool $oldestFirst = false): array` | Returns soft-deleted records by `updated_at`, paged and ordered the same way. |
| `listTotals(): array` | `{posts, drafts, deleted}` — how many rows each of the three lists holds, so the browser knows how much further a block can scroll. |
| `saveDraft(string $text, array $imageUrls, ?ForumPublicationRef $forumRef = null): void` | Validates text length (1 and 4096 chars) and stores the record as a **draft**. If a forum reference is supplied, a temporary link is stored. |
| `schedulePost(string $text, array $imageUrls, string $publishedAt, ?ForumPublicationRef $forumRef = null, ?string $userTimezone = null): void` | Validates text, normalises the supplied date (several common formats are supported), creates a **scheduled** post (`published_at` set) and stores an optional forum link. |
| `saveParts(array $texts, array $imageUrls, string $publishedAt, string $action, ?ForumPublicationRef $forumRef = null, ?string $userTimezone = null, bool $distributeImages = false): void` | Entry point of the publication form for a **new** record, which sends one text field per part of a long publication. Every part goes through the text validation before anything is written. A single field is saved exactly as `saveDraft()`/`schedulePost()` would. Several fields become one record each through a single repository call (`createPosts()`/`createDrafts()`), so a failure halfway leaves none of the parts behind; `$action` (`'draft'` or `'publish'`) picks the table, the forum link belongs to the first record, and its `published_at` is the form date with each following part one minute later, so `publishDue()` drains the parts in order. The images go to the first record too, unless `$distributeImages` asks `groupImages()` to hand every part its own contiguous slice of the list. |
| `saveFromForm(string $text, array $imageUrls, string $publishedAt, string $source, ?int $sourceId, string $action, ?string $userTimezone = null): void` | The eight scenarios of the form when it holds a record opened for editing (`$source` is `post`, `draft` or `deleted`, `$action` is `'draft'` or `'publish'`): update in place, or move between the posts, drafts, edited and deleted tables. `created_at` is always preserved, `updated_at` is the moment of the change, nothing is sent to Telegram from the form. See «Сценарии сохранения» in [README.md](README.md). |
| `publishDue(): array` | Finds all scheduled posts whose `published_at` is in the past and whose `telegram_id` is empty, sends them to Telegram via `ChannelService`, stores the returned `telegram_id`, and marks the publication as **published**. Returns a stats array `{processed, published, failed}`. |
| `deleteDue(): array` | Processes rows in `publications_deleted` with an empty `deleted_at`, removes the message from Telegram by `telegram_id` (if present), then sets `deleted_at` to current time. Returns a stats array `{processed, deleted, failed}`. Records without `telegram_id` are marked as deleted immediately (no message existed in Telegram). |
| `editDue(): array` | Processes rows in `publications_edited` with an empty `edited_at`, updates the Telegram message text by `telegram_id`, then sets `edited_at` to current time. Returns a stats array `{processed, edited, failed}`. |
| `publishDraft(int $id): void` | The «Опубликовать» button on a draft: the record moves to the posts table with `published_at` and `updated_at` set to the current time, so the periodic `publishDue()` task sends it to Telegram at once. `created_at` is preserved. |
| `scheduleDraft(int $id, string $publishedAt, ?string $userTimezone = null): void` | The «Запланировать публикацию» modal: the draft moves to the posts table with the publication time from the modal. `created_at` is preserved, `updated_at` is the current time. |
| `publishPostNow(int $id): void` | The «Опубликовать» button on a scheduled post: the post stops being scheduled — `published_at` and `updated_at` become the current time, `created_at` is untouched. |
| `movePostToDraft(int $id): void` | The «Переместить в черновик» button: the post row is deleted from the posts table and inserted into the drafts table; `created_at` is preserved, `updated_at` is the moment of the move. |
| `publishDeleted(int $id): void` | Restores a soft-deleted publication to the posts table with current timestamp as `published_at`, clears `telegram_id` (new message will be sent), and removes the deleted record. Used for republishing deleted content. |
| `scheduleDeleted(int $id, string $publishedAt, ?string $userTimezone = null): void` | Restores a soft-deleted publication to the posts table with the specified `publishedAt`, clears `telegram_id`, and removes the deleted record. Used for rescheduling deleted content. |
| `moveDeletedToDraft(int $id): void` | Moves a soft-deleted publication to the drafts table, clearing `telegram_id` and setting `updated_at` to current time. Used for recovering deleted content as a draft. |
| `deleteDraft(int $id): void` | Moves a draft into `publications_deleted` so the pending removal is dropped and the record stays restorable. |
| `deletePost(int $id): void` | Soft-deletes a published/scheduled post by moving it to `publications_deleted` table, preserving `created_at` and `published_at`, setting `updated_at` to current time, and clearing `telegram_id` (the periodic task will handle Telegram deletion). |

### Validation Rules
- Text content must be between 1 and 4096 characters (inclusive). The limit is per record, and a long publication is split into parts by the form before saving, so each stored part stays inside it (see `saveParts()` and «Разбивка длинного текста на части» in [README.md](README.md))
- Image URLs array can be empty but must contain valid URLs when provided
- Dates must be parseable by multiple common formats (see `normalizeDate()` method)
- Forum references are optional but when provided must contain valid source ('topic' or 'post') and positive sourceId

### Error Handling
- `InvalidArgumentException` is thrown for validation failures (text length, date format, missing IDs, etc.)
- `TelegramApiException` is thrown when Telegram API calls fail (network issues, invalid bot token, rate limiting, etc.)
- `RuntimeException` is thrown when Telegram bot is not configured (missing TELEGRAM_BOT_TOKEN)

---

## Telegram Integration – `ChannelService`

Located at `app/shared/Telegram/Service/ChannelService.php`. The service is a thin wrapper around the low-level `TelegramChannelClientInterface` and adds business-level validation.

| Method | Purpose |
|--------|---------|
| `isConfigured(): bool` | Returns **true** if a bot client instance is provided. |
| `channelInfo(): ChannelInfo` | Retrieves basic channel information (id, title, username, description). |
| `updateDescription(string $description): void` | Validates that the description does not exceed **255** characters and updates the channel description via the Telegram API. The new description is archived in the `publishedDescriptions` repository. |
| `publishedDescriptions(): array` | Returns archived channel descriptions (newest first). |
| `publishText(string $text): int` | Sends a plain-text message (1–4096 chars). Returns the Telegram `message_id`. |
| `publishPhotos(string $text, array $photoUrls): int` | Sends one or many photos (up to 10 per API call). The first photo receives a caption (max 1024 chars), cut at the last line break that still fits so a line is never split. What does not fit into the caption is sent once as a continuation text message after the photos, never as a repeat of the whole text. Returns the `message_id` of the first photo. |
| `pinPost(int $messageId): void` | Pins the given message in the channel. |
| `deletePost(int $messageId): void` | Deletes a message from the channel. |
| `editPostText(int $messageId, string $text): void` | Edits the text of an existing message (same validation as `publishText`). |

All methods may throw `TelegramApiException` (API failure) and `RuntimeException` when the bot token is missing.

The limits are constants of the class: `DESCRIPTION_MAX_LENGTH` (255) and `CAPTION_MAX_LENGTH` (1024) are private, while `TEXT_MAX_LENGTH` (4096) is public because the publications form reads it to decide where a long text has to be split into parts.

---

## Console Commands (`app/commands/TelegramController.php`)

The console controller exposes the above functionality to the command line and is intended to be used by cron jobs or developers.

| Command | Description |
|---------|-------------|
| `php yii telegram/index` | Shows current channel information (id, title, username, description). |
| `php yii telegram/set-description "<desc>"` | Updates the channel description. Returns an error code if validation fails or the bot is not configured. |
| `php yii telegram/test-post "<text>" [--pin=0|1]` | Sends a test text message. Optionally pins the message. |
| `php yii telegram/publish-due` | Executes `PublicationsService::publishDue()` - publishes all due scheduled posts. |
| `php yii telegram/delete-due` | Executes `PublicationsService::deleteDue()` - removes messages from Telegram for soft-deleted publications. |
| `php yii telegram/edit-due` | Executes `PublicationsService::editDue()` - updates the text of edited publications in Telegram. |

These commands are typically scheduled via **cron** using the environment variables defined in `.env` (see below).

---

## Cron Scheduling (environment variables)

| Variable | Default | Description |
|----------|---------|-------------|
| `TELEGRAM_PUBLISH_CRON_SCHEDULE` | `*/5 * * * *` | How often `telegram/publish-due` should run. |
| `TELEGRAM_DELETE_CRON_SCHEDULE` | `*/5 * * * *` | How often `telegram/delete-due` should run. |
| `TELEGRAM_EDIT_CRON_SCHEDULE` | `*/5 * * * *` | How often `telegram/edit-due` should run. |

These variables are read by the **docker-compose** entrypoint (or by a host scheduler) and passed to the container. Adjust them according to your desired frequency.

---

## Configuration (`.env`)

The Telegram integration requires two variables:

```
TELEGRAM_BOT_TOKEN=xxxx:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TELEGRAM_CHANNEL_ID=@your_channel_username   # or numeric channel ID
```

If the token is missing the service will throw a `RuntimeException` and all Telegram-related commands will exit with the `CONFIG` exit code.

---

## Usage from the Web UI

The **SiteController** (`app/controllers/SiteController.php`) provides a UI for managing the channel description and for viewing the connection status. The dashboard (`views/site/index.php`) shows whether the channel is reachable.

The publications page (`/publications`) and its form posts to `publication-create`, which calls `PublicationsService::saveParts` for a new record and `saveFromForm` for a record opened for editing; the per-record buttons call `publishDraft`/`publishPostNow`, `movePostToDraft` and `deletePost`/`deleteDraft` through `publication-publish`, `publication-to-draft` and `publication-delete`. The image links a fill brings in (`data-image-urls`, from the record or from the forum) are probed in the browser before they settle in the `publicationImages` field: the whole list goes in at once so a submit made mid-probe loses nothing, only links that answer a load are kept, a link that never answers is kept too, and the ones dropped are named in `#publicationImagesNotice` under the field (see «Открытие на редактирование» in [README.md](README.md)). The three record lists start at one page of `PUBLICATIONS_PAGE_SIZE` rows and ask `publication-page` (`posts()`/`drafts()`/`deleted()` with a page and an offset, rendered through the `_item_*` partials) for the next page whenever a block is scrolled to its bottom. The forum block pages on two levels of its own: its topics come from the same `publication-page` endpoint (`ForumRepository::latestTopicsWithPosts` with an offset, `countTopics` for the total), while the posts of one topic sit in a scrollable box of their own (`_item_topic`, `.thread-replies`) and are loaded by `forum-post-page` (`ForumRepository::topicPosts`) as that box is scrolled — see «Бесконечная прогрузка списков» in [README.md](README.md). A switch in the header of a block posts to `publication-sort`, which stores the reading order of that list in the session and answers with the block the switch belongs to redrawn from its first page in the new order — the forum block has two such switches, one for its topics (`ForumRepository::latestTopicsWithPosts` topic order) and one for the posts of each topic; the session state — the forum filters and the reversed lists alike — is mirrored into the address bar (`SiteController::publicationsUrl`, `?postsOldest=1` and friends), and a page opened on an address that disagrees with the session is redirected onto it (see «Сортировка списков» in [README.md](README.md)). Addresses are flat: `UrlManager` maps each of these routes onto its own path without `index.php?r=` (see «Адреса страниц» in [README.md](README.md)). After a scheduled post becomes due the background `publish-due` command sends it to Telegram.

---

## Testing

Codeception suites live in `app/tests`; the installed PHPUnit requires PHP >= 8.4, and `Unit`, `Functional` and `Acceptance` run against the test configuration in `app/config/test.php`, which binds `PublicationRepositoryInterface` to the real repository, so repository tests talk to a PostgreSQL test database.

- `Unit/shared/Publications/Infrastructure/PublicationRepositoryTest` — rows written by `createDraft`/`createPost`, the sort order of the three lists, what `findDueForPublishing` selects, and moving records back out of `publications_deleted`
- `Unit/shared/Publications/Infrastructure/CachePublicationForumLinkStoreTest` — remember/find/move/forget of the temporary forum link
- `Unit/shared/Telegram/Service/ChannelServicePublishPhotosTest` — album grouping, caption truncation and the continuation message, with the low-level Telegram client mocked
- `Unit/shared/Telegram/Infrastructure/PublishedDescriptionRepositoryTest` — channel description persistence

The service use-cases themselves (`saveParts`, `saveFromForm`, the periodic tasks) have no automated test coverage, and no test exercises the publications page in a browser.

---

## References

- **Telegram Bot API** – https://core.telegram.org/bots/api
- **Yii 2 Dependency Injection** – https://www.yiiframework.com/doc/guide/2.0/en/concept-di
- **Publications Service Design** – see source code under `app/shared/Publications/Service/`
- **Channel Service Design** – see source code under `app/shared/Telegram/Service/`

---