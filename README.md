# MindCat API

REST API for MindCat, a mental health continuity platform that connects patients and psychologists between therapy sessions. The API is the core of the product: the web client and the (currently frozen) mobile client are two consumers of the same backend.

Built with Laravel 13 and Laravel Sanctum. Test suite covers 173 cases running against SQLite in memory.

## What it does

The API handles authentication, patient and professional accounts, daily mood tracking, an encrypted personal diary, therapeutic tasks, consent-based links between psychologists and patients, professional credential verification, and an admin review panel for those credentials.

Access is separated by role. A `patient` records mood and diary entries and completes tasks. A `pro` links to patients, reads a clinical summary, and assigns tasks, but only after their professional credential has been reviewed and approved. An `admin` reviews the credential queue.

## Stack

- PHP 8.1+
- Laravel 13
- Laravel Sanctum 4 for token authentication
- MySQL in development and production, SQLite in memory for tests
- PHPUnit for the test suite

## Authentication

The web client uses short-lived access tokens held in memory, paired with a refresh token delivered as an `HttpOnly` cookie scoped to the refresh route. Access tokens carry an `access` ability and expire in 30 minutes. Refresh tokens carry a `refresh` ability, last 30 days, and rotate on every use: each call to the refresh endpoint deletes the old pair and issues a new one under the same session name, so both tokens can be revoked together.

The mobile client uses standard Sanctum bearer tokens against the same endpoints. Both flows coexist.

Browser storage such as `localStorage` is deliberately not used for tokens, since the application handles sensitive health data.

## Data protection

Diary content is encrypted at rest with a dedicated key that is separate from the application key, so that compromising one does not expose the other. Each row records its encryption version to support key migration without downtime.

The diary is protected by a password that is separate from the account password and is never stored in plain text. The account owner and the linked psychologist cannot read diary content without that password.

Account deletion follows LGPD requirements. Diary entries and mood logs are permanently deleted, tasks are preserved as clinical records, links are deactivated, and the user record is anonymized and then soft-deleted.

## Requirements

- PHP 8.1 or higher
- Composer
- MySQL 8

## Setup

```bash
git clone https://github.com/Kuligowskilucas/MindCatApi.git
cd MindCatApi
composer install
cp .env.example .env
php artisan key:generate
```

Set `MINDCAT_DIARY_KEY` in `.env` to a base64 key. It must be backed up separately from the database: losing it means losing every diary entry, with no recovery.

Configure the database credentials in `.env`, then run:

```bash
php artisan migrate --seed
php artisan serve --host=localhost --port=8000
```

The API must be served on `localhost` rather than `127.0.0.1`, since browsers treat the two as different origins and the session cookie will not be sent on the IP form.

## Tests

```bash
php artisan test
```

Tests run against SQLite in memory with fixed `APP_KEY` and `MINDCAT_DIARY_KEY` values defined in `phpunit.xml`. Those keys are disposable test values and are never used in production.

## Related repositories

- Web client: https://github.com/Kuligowskilucas/MindCatWeb
- Mobile client (frozen): https://github.com/Kuligowskilucas/mindcat

## Status

In active development. Not yet in production. The web client is the current focus; the mobile client is frozen until the web version ships.

## License

This repository is public for portfolio purposes. It is not licensed for reuse.