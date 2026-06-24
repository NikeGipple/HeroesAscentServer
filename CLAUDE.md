# HeroesAscentServer — Context

@AGENTS.md

## State
~88 incremental releases (versioned `0.0XX.YYYYMMDD`) since November 2025, active near-daily commit cadence, linear history on a single branch.

## Data model
- `Account` — GW2 API key, UUID `account_token`, `account_name`, `active`.
- `Character` — one leveling attempt per account/edition; `level`, `profession`, `score`, `disqualified_at`.
- `CharacterEvent` — full telemetry snapshot per addon-reported event (map, profession, position, group state, buff, etc.) plus `event_code`, `points`, `detected_at`.
- `EventType` — the rule catalog: `code`, `category`, `points`, `is_critical`, `color`. See `database/seeders/EventTypeSeeder.php` for the current full list (login/death/movement/violation/build/buff categories).
- `BannedSkill`, `BannedTrait`, `ForbiddenMap` — data-driven restriction lists, synced from the official GW2 API via artisan commands.
- `Profession`, `Specialization` — GW2 lookup tables.

## API surface (`routes/api.php`)
- `POST /api/account/register` — GW2 API-key validation (needs `account`+`progression` scopes), AP-threshold check, guild check (see below), issues `account_token`.
- `POST /api/account/check` — addon validates its stored token before play.
- `POST /api/character/update` — main telemetry ingestion; creates character on first LOGIN, validates level progression (+1 increments or the L1→L3 tutorial jump), records the event with points/critical flag, auto-disqualifies on critical events unless the account is in `bypass_accounts`.
- `GET /api/status` — heartbeat.

## Verified nuance (don't re-flag this as a bug)
The guild check is intentionally permissive in the right direction: zero guild memberships passes (satisfies "must not be in a guild"); membership in exactly one specific hardcoded guild ID ("Heroes Ascent Official Guild") also passes; anything else is blocked (`RegistrationController.php:112-130`, mirrored in `Gw2RulesService.php`). The only real nit is that the guild ID is a hardcoded string rather than config.

See root `CLAUDE.md` for the ruleset gaps this server doesn't enforce yet, and `Plan/TODO.md` for the prioritized backlog.
