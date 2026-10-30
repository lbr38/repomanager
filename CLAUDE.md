## Approach
- Reason only as much as the task needs; keep output concise.
- No sycophantic openers or closing fluff.
- No emojis or em-dashes.
- Do not guess APIs, versions, flags, commit SHAs, or package names. Verify by reading code or docs before asserting.

## Token efficiency
- Read only files directly needed, and only the relevant functions/sections. Skip files over 100KB unless required. Ask before broad scans.
- Do not re-read files or restate context already established, unless the files changed.
- Keep plans to 3-5 steps unless complexity requires more.
- For edits, show only the changed section, not full file.
- Default to brief answers. Expand only if asked.

## Project layout
PHP application (web UI, API, background tasks). Code lives in `www/`:
- `controllers/` business logic, one subfolder per domain (Repo, Host, Task, Api, User, ...). Namespaced classes, loaded by `controllers/Autoloader.php`.
- `models/` database access, one model per domain, extends `models/Model.php`.
- `views/` HTML rendering (`*.template.php`, layouts). `templates/` non-HTML templates (app.yaml, mail, source repositories). `public/` web root.
- `tasks/execute.php` task runner. `tools/` CLI scripts (CVE import, service, database). `bin/` service helpers.
- `update/` migrations and release update scripts. `version` holds the current version.
- `libs/` third-party (composer, `libs/vendor`). Never edit or read it.
- Outside `www/`: `docker/` and `Dockerfile` (image), `ansible/` (role), `documentation/`, `.github/workflows/` (CI).

## Checks
- PHPStan: `php www/libs/vendor/bin/phpstan analyse -c phpstan.neon www` (level 0, config in `phpstan.neon`).
- PHPCS: `php phpcs.phar --standard=.github/lint/phpcs.xml -n . --ignore=libs/,*.js` (needs `phpcs.phar`, not in repo). Follow the rules in `.github/lint/phpcs.xml`.
- No PHPUnit suite; other CI tests are in `.github/workflows/` (API, database update, ansible role).
