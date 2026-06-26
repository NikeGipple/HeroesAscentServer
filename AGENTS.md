# HeroesAscentServer — Guidelines

@../AGENTS.md

Stack-specific rules for this Laravel backend. Ecosystem-wide rules (data-driven rules, server owns enforcement, minimal data collection, etc., in the imported file above) apply here in full.

## Stack
- Laravel 12, PHP 8.2+, MySQL 8.
- Frontend: React 19 + Redux Toolkit + React Router 7 + Tailwind 4 + Bootstrap 5, built via Vite.
- Tests: PHPUnit (Feature/Unit) — currently minimal, mostly framework examples.

## Conventions already in use — follow them
- Business logic lives in `app/Services/` (`Gw2ApiService`, `CharacterEventWorkflow`, `CharacterEventRecorder`, `Gw2RulesService`), not in controllers. New rule logic goes in a service, not a fat controller method.
- Rule data lives in DB-backed models (`BannedSkill`, `BannedTrait`, `ForbiddenMap`, `EventType`), kept in sync with the official GW2 API via `app/Console/Commands/Sync*.php` artisan commands. Add new forbidden-thing categories as a model + sync command, not a hardcoded array.
- `EventType.is_critical` drives auto-disqualification; `EventType.points` drives scoring. Both are data, set via seeders/admin — don't branch on event `code` strings for these decisions outside the seeder.
- `config/heroesascent.php` + `HA_BYPASS_ACCOUNTS` env var is the pattern for anything that needs to be organizer-tunable without a deploy. Prefer extending this over hardcoding (e.g. the guild-allowlist ID in `RegistrationController`/`Gw2RulesService` should probably move here).
- Soft deletes + denormalized event snapshots (`CharacterEvent`) exist for audit/forensics — don't strip context fields to "clean up" payloads; organizers need the raw snapshot to review disputed disqualifications.
- `Gw2ApiService` already rate-limits and retries calls to the official GW2 API (5 req/s, 3 retries, explicit 429/`Retry-After` handling in `safeRequest`) — reuse it rather than calling the GW2 API directly elsewhere. This is the best-practice reference for outbound GW2 API etiquette in this workspace — see root `AGENTS.md` § "GW2 API usage — rate limits & etiquette" for the research behind it (links, current rate-limit numbers as of the research date, ID-batching guidance). The 5 req/s figure is hardcoded here, not read from the API's own `x-rate-limit-limit` response header — that header's actual value has been observed to differ from the commonly-cited number, so don't treat 5 req/s as a permanently-correct constant.
- Never call `api.guildwars2.com` directly outside `Gw2ApiService` — it's the one place the throttle/retry/429-handling lives; a direct call anywhere else silently loses that protection and risks tripping the shared per-IP rate limit (shared with any other GW2 tooling running on the same machine/network, including `gw2-mcp`/`gw2-chatlinks-go` in this same workspace).
- Never try to circumvent ArenaNet's rate limits or terms (IP rotation, User-Agent spoofing, etc.) — see root `AGENTS.md` hard constraint 8.

## Gaps to keep in mind
- No rate limiting on addon-facing endpoints (`/api/character/update` etc.) — don't assume the addon is the only caller.
- No admin API yet for organizers to manage violations or override a disqualification.
- No leaderboard endpoint despite the README mentioning one; an external leaderboard (leaderboarded.com) is currently linked from the rules doc instead.
- Minimal automated test coverage on the validation/scoring path — be cautious changing `CharacterEventWorkflow` without adding a regression test alongside.
- `Gw2ApiService::safeRequest`'s 429 handling recurses with no max-retry cap — a sustained 429 (e.g. a real ArenaNet-side outage or limit change) could recurse for a long time. Worth a bounded retry count; see `docs/TODO.md`.
