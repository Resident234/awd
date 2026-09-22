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

### Core DTOs and Interfaces

- **`PublicationData` (DTO)** - Represents a publication record with fields:
  - `id` (int): Primary key
  - `text` (string): Publication content (1-4096 characters)
  - `imageUrls` (string[]): Array of image URLs
  - `publishedAt` (string): Publication timestamp (YYYY-MM-DD HH:MM:SS format)
  - `createdAt` (string): Creation timestamp
  - `updatedAt` (string): Last update timestamp
  - `telegramId` (int|null): Telegram message ID (null until published)
  - `forumRef` (ForumPublicationRef|null): Optional forum source reference

- **`PublicationRepositoryInterface`** - Abstract repository used by the service to persist publications:
  - `createDraft(string $text, array $imageUrls, string $now): int` - Creates a draft
  - `createPost(string $text, array $imageUrls, string $publishedAt, string $now): int` - Creates a scheduled/post
  - `updateDraft(int $id, string $text, array $imageUrls, string $now): void` - Updates a draft
  - `updatePost(int $id, string $text, array $imageUrls, string $publishedAt, string $now): void` - Updates a scheduled/post
  - `delete(int $id): void` - Soft-deletes a publication
  - `restoreFromDeleted(int $id, string $publishedAt): int` - Restores from deleted to post table
  - `moveToEdited(int $id): void` - Moves published post to edited archive
  - `findPost(int $id): PublicationData|null` - Finds a post by ID
  - `findDraft(int $id): PublicationData|null` - Finds a draft by ID
  - `findDeleted(int $id): PublicationData|null` - Finds a deleted record by ID
  - `allPosts(): PublicationData[]` - Gets all posts (scheduled and published)
  - `allDrafts(): PublicationData[]` - Gets all drafts
  - `allDeleted(): PublicationData[]` - Gets all soft-deleted records
  - `allEdited(): PublicationData[]` - Gets all edited records

- **`ForumPublicationRef`** - Optional reference to a forum entity that originated the publication (used for linking publications to forum topics):
  - `source` (string): Either 'topic' or 'post'
  - `sourceId` (int): ID of the forum topic or post

- **`PublicationForumLinkStoreInterface`** - Temporary store that remembers the relation between a newly created draft/post and a forum entity until the publication is finally published:
  - `store(int $publicationId, ForumPublicationRef $ref): void` - Stores the link
  - `recall(int $publicationId): ForumPublicationRef|null` - Recalls the link
  - `move(int $fromPublicationId, int $toPublicationId): void` - Moves link between publication IDs
  - `forget(int $publicationId): void` - Removes the link

- **`ForumPublicationMapGatewayInterface`** - Permanent bridge table that stores the link between a forum entity and a Telegram message (`telegram_id`):
  - `insert(int $forumEntityId, int $telegramMessageId): void` - Creates permanent link
  - `updateTelegramId(int $forumEntityId, int $telegramMessageId): void` - Updates telegram ID for existing link
  - `findByForumEntityId(int $forumEntityId): int|null` - Finds telegram ID by forum entity ID

The concrete implementations live in the **Infrastructure** sub-namespace of each module and are wired via Yii DI container.

---

## Service API – `PublicationsService`

The service lives in `app/shared/Publications/Service/PublicationsService.php` and implements the following public use-cases:

| Method | Description |
|--------|-------------|
| `posts(): array` | Returns all *published* posts (including scheduled ones that are already due) ordered by `published_at` descending. |
| `drafts(): array` | Returns all drafts ordered by `updated_at` descending. |
| `deleted(): array` | Returns all soft-deleted records ordered by `updated_at` descending. |
| `edited(): array` | Returns all edited publication records ordered by `updated_at` descending. |
| `saveDraft(string $text, array $imageUrls, ?ForumPublicationRef $forumRef = null): void` | Validates text length (1 and 4096 chars) and stores the record as a **draft**. If a forum reference is supplied, a temporary link is stored. |
| `schedulePost(string $text, array $imageUrls, string $publishedAt, ?ForumPublicationRef $forumRef = null): void` | Validates text, normalises the supplied date (several common formats are supported), creates a **scheduled** post (`published_at` set) and stores an optional forum link. |
| `updateDraft(int $id, string $text, array $imageUrls, ?ForumPublicationRef $forumRef = null): void` | Updates an existing draft with new content and/or images. If a forum reference is supplied, updates the temporary link. |
| `updatePost(int $id, string $text, array $imageUrls, string $publishedAt, ?ForumPublicationRef $forumRef = null): void` | Updates an existing scheduled/post with new content, images, and/or publication time. If a forum reference is supplied, updates the temporary link. Moving to future/past schedules the post appropriately. |
| `publishDue(): array` | Finds all scheduled posts whose `published_at` is in the past and whose `telegram_id` is empty, sends them to Telegram via `ChannelService`, stores the returned `telegram_id`, and marks the publication as **published**. Returns a stats array `{processed, published, failed}`. |
| `deleteDue(): array` | Processes rows in `publications_deleted` with an empty `deleted_at`, removes the message from Telegram by `telegram_id` (if present), then sets `deleted_at` to current time. Returns a stats array `{processed, deleted, failed}`. Records without `telegram_id` are marked as deleted immediately (no message existed in Telegram). |
| `editDue(): array` | Processes rows in `publications_edited` with an empty `edited_at`, updates the Telegram message text by `telegram_id`, then sets `edited_at` to current time. Returns a stats array `{processed, edited, failed}`. |
| `publishDeleted(int $id): void` | Restores a soft-deleted publication to the posts table with current timestamp as `published_at`, clears `telegram_id` (new message will be sent), and removes the deleted record. Used for republishing deleted content. |
| `scheduleDeleted(int $id, string $publishedAt): void` | Restores a soft-deleted publication to the posts table with specified `publishedAt`, clears `telegram_id`, and removes the deleted record. Used for rescheduling deleted content. |
| `moveDeletedToDraft(int $id): void` | Moves a soft-deleted publication to the drafts table, clearing `telegram_id` and setting `updated_at` to current time. Used for recovering deleted content as a draft. |
| `deleteDraft(int $id): void` | Permanently removes a draft from the system. |
| `deletePost(int $id): void` | Soft-deletes a published/scheduled post by moving it to `publications_deleted` table, preserving `created_at` and `published_at`, setting `updated_at` to current time, and clearing `telegram_id` (the periodic task will handle Telegram deletion). |
| `getPost(int $id): PublicationData|null` | Retrieves a specific publication by ID from the posts table. |
| `getDraft(int $id): PublicationData|null` | Retrieves a specific draft by ID from the drafts table. |
| `getDeleted(int $id): PublicationData|null` | Retrieves a specific soft-deleted record by ID from the deleted table. |

### Validation Rules
- Text content must be between 1 and 4096 characters (inclusive)
- Image URLs array can be empty but must contain valid URLs when provided
- Dates must be parseable by multiple common formats (see `normalizeDate()` method)
- Forum references are optional but when provided must contain valid source ('topic' or 'post') and positive sourceId

### Error Handling
- `InvalidArgumentException` is thrown for validation failures (text length, date format, missing IDs, etc.)
- `TelegramApiException` is thrown when Telegram API calls fail (network issues, invalid bot token, rate limiting, etc.)
- `RuntimeException` is thrown when Telegram bot is not configured (missing TELEGRAM_BOT_TOKEN)

---pty `deleted_at`. If `telegram_id` is present the message is deleted from the Telegram channel. The row is then timestamp-ed with `deleted_at`. Returns stats. |
| `editDue(): array` | Processes rows in `publications_edited` with an empty `edited_at`. The stored text is used to edit the corresponding Telegram message via `ChannelService`. The row is then timestamp-ed with `edited_at`. Returns stats. |

All methods throw `InvalidArgumentException` for validation errors and `RuntimeException` when the Telegram bot is not configured.

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

The publication forms (not shown here) call `PublicationsService::saveDraft` or `schedulePost` via the controller actions under the `publications` route. After a scheduled post becomes due the background `publish-due` command sends it to Telegram.

---

## Testing

- Unit tests for `PublicationsService` verify that drafts are saved, scheduling works, validation errors are thrown, and that the due-publishing flow correctly invokes `ChannelService` (mocked).
- Integration tests for `ChannelService` mock the low-level Telegram client to ensure proper validation and error handling.
- Console commands are covered by functional tests that assert the correct exit codes and output.

---

## References

- **Telegram Bot API** – https://core.telegram.org/bots/api
- **Yii 2 Dependency Injection** – https://www.yiiframework.com/doc/guide/2.0/en/concept-di
- **Publications Service Design** – see source code under `app/shared/Publications/Service/`
- **Channel Service Design** – see source code under `app/shared/Telegram/Service/`

---