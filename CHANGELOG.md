# Changelog

All notable changes to `laravel-starter` will be documented in this file.

## v1.1.5 - 2026-04-20

### What's new

- **Boost + Sail detection** — `starter:finalize` now re-runs `php artisan boost:install` when `boost.json` is present, and commits the result
- **AI agent directories excluded from git** — `updateGitignore()` now appends `/.claude`, `/.agents`, `/.amp`, `/.codex`, `/.gemini`, `/.junie`, `/.kiro` and `/.github/skills`
- **CLI output with Laravel Prompts** — all commands use `task()` from Laravel Prompts (with `Logger` for streaming process output) followed by `info('✓ ...')` for a guaranteed validation tick after each step; `Runner::run()` accepts `?Logger` and falls back to `tty()` when none is provided
- **Dev scripts** — `scripts/setup-local.sh <project>` adds the path repository and `minimum-stability: dev` to a target project's `composer.json`; `scripts/setup-sail.sh <project>` mounts the package into the project's Sail compose file

**Full Changelog**: https://github.com/ngiraud/laravel-starter/compare/v1.1.4...v1.1.5

## v1.1.4 - 2026-04-17

### What's new

- **Sail `require-dev` in project** — when Sail is selected during `starter:install`, `laravel/sail` is now added as a `require-dev` in the project's own `composer.json` if not already present, so the project retains the dependency after the starter package is removed
- **Split AI guidelines** — `.ai/guidelines/` stubs are now split into topic files (`conventions.md`, `testing.md`, `actions.md`, `enums.md`); `actions.md` is only published when the Action design pattern is confirmed, `enums.md` only when the EnhanceEnum trait is confirmed

**Full Changelog**: https://github.com/ngiraud/laravel-starter/compare/v1.1.3...v1.1.4

## v1.1.3 - 2026-04-17

Fix PHPStan errors on `array_merge` calls with mixed-typed values in `UpdateComposerScriptsAction` and `UpdatePackageJsonAction`.

**Full Changelog**: https://github.com/ngiraud/laravel-starter/compare/v1.1.2...v1.1.3

## v1.1.2 - 2026-04-17

Skip npm lint scripts when eslint/prettier are not installed - 2026-04-17

**Full Changelog**: https://github.com/ngiraud/laravel-starter/compare/v1.1.1...v1.1.2

## v1.1.1 - 2026-04-17

**Full Changelog**: https://github.com/ngiraud/laravel-starter/compare/v1.1.0...v1.1.1

## v1.1.0 - 2026-04-16

### Breaking changes

- `starter:install` is now the only orchestrator command — the old monolithic `LaravelStarterCommand` has been removed
- `config/starter.packages` format changed: entries are now keyed arrays (`label`, `require`, `dev`, `default`, `version?`, `installer?`, `modifies_console?`) instead of class
  references
- `ProcessRunner` facade and `PackagesCollection` have been removed
- `make:action` is no longer registered as a package command — it must be published to the project via the Action design pattern confirm in `starter:publish`
- `composer refactor` script replaced by `composer lint` (which now runs Rector + Pint + ESLint together)

### What's new

- **`starter:init`** — standalone command: git init + .env configuration (app name, locale, database)
- **`starter:add {package}`** — install a single package + post-install steps + commit; can be run at any time after setup
- **`starter:remove {package}`** — remove an installed package + cleanup + commit; warns when `modifies_console: true` and no `uninstall()` method
- **`starter:publish`** — publish config stubs, update scripts, gitignore, and opt-in confirms for AI guidelines, Action design pattern, and EnhanceEnum trait; accepts
  `--docker-services` option
- **`starter:finalize`** — run `composer lint` (Rector + Pint + ESLint) and commit
- **`starter:install` delegates to sub-commands** — packages loop calls `starter:add`, publish step calls `starter:publish`, finalize step calls `starter:finalize`
- **Action design pattern** — opt-in confirm publishes `Action`, `Fakeable`, `FakeAction`, `FakeableTest`, and `MakeActionCommand` to the project
- **EnhanceEnum trait** — separate opt-in confirm in `starter:publish` / `starter:install`
- **Sail is now optional** — `starter:install` asks whether to use Sail; `Runner` auto-detects from compose file presence for standalone commands; TTY disabled in non-terminal
  environments
- **Installers** — post-install/pre-remove logic in focused classes (`TelescopeInstaller`, `HorizonInstaller`, `FilamentInstaller`, `LaravelBackupInstaller`, `LarastanInstaller`,
  `RectorInstaller`)
- **Self-remove** — `starter:install` proposes `composer remove ngiraud/laravel-starter` at the end of installation
- **AI guidelines** — opt-in confirm to copy `.ai/guidelines` stubs into the project
- **Flysystem S3 adapter** — automatically required when `minio` or `rustfs` is selected as a Sail service
- **GitHub Actions workflows are now conditional** — `phpstan.yml` only copied if Larastan is installed, `rector.yml` only if Rector is installed
- **`.claude/` added to `.gitignore`** before the first commit so Boost-generated files are never committed
- **Test suite** — full Pest test suite via Orchestra Testbench covering all actions, commands, `Git`, and `Runner`

### What's changed

- `phpstan.yml` stub no longer includes a PostgreSQL service
- `UpdateEnvironmentAction` signature simplified: individual parameters instead of a preferences array
- `PublishFilesAction` no longer publishes the User model stub — too project-specific
- Boost removed from the packages list — only guidelines publishing remains
- `horizon` default changed to `false`
- All installer classes now extend abstract `Installer` base class; `STUBS_PATH` constant replaced by `stubsPath(?string $path)` method (overridable per installer)
- New `Uninstallable` interface for installers that support `uninstall()`; `RemovePackageCommand` uses `instanceof Uninstallable` instead of `method_exists`
- Package array shape extended with `modifies_console?: bool` key
- `env()` calls in `PublishCommand` replaced with typed `config()->string()` accessors
- `UpdateComposerScriptsAction::buildScripts()` refactored to use plain typed arrays instead of untyped collections

**Full Changelog**: https://github.com/ngiraud/laravel-starter/compare/v1.0.13...v1.1.0

## v1.0.13 - 2026-01-20

** What's changed **

- Update Readme.md

**Full Changelog**: https://github.com/ngiraud/laravel-starter/compare/v1.0.12...v1.0.13

## v1.0.12 - 2026-01-20

** What's changed**

- Trying to fix how Boost guidelines are copied

**Full Changelog**: https://github.com/ngiraud/laravel-starter/compare/v1.0.11...v1.0.12

## v1.0.11 - 2026-01-20

** What's changed**

- Guidelines for Laravel Boost

**Full Changelog**: https://github.com/ngiraud/laravel-starter/compare/v1.0.10...v1.0.11

## v1.0.10 - 2026-01-20

** What's changed**

- Copy Laravel guidelines for Boost

**Full Changelog**: https://github.com/ngiraud/laravel-starter/compare/v1.0.9...v1.0.10

## v1.0.9 - 2026-01-20

** What's changed **

- Update AppServiceProvider
- Bump FIlament version from 4 to 5

**Full Changelog**: https://github.com/ngiraud/laravel-starter/compare/v1.0.8...v1.0.9

## v1.0.8 - 2026-01-08

### What's Changed

* Fix PHPStan error

**Full Changelog**: https://github.com/ngiraud/laravel-starter/compare/v1.0.7...v1.0.8

## v1.0.7 - 2026-01-07

### What's Changed

* chore(deps): bump actions/checkout from 4 to 6 by @dependabot[bot] in https://github.com/ngiraud/laravel-starter/pull/5
* chore(deps): bump stefanzweifel/git-auto-commit-action from 6 to 7 by @dependabot[bot] in https://github.com/ngiraud/laravel-starter/pull/4
* Add 2FA on User model
* Update Rector package, using the custom for Laravel
* Composer scripts are now correctly updated, keeping "setup" and "pre/post" scripts
* Change Docker service preferences, using now PGSql, RustFS and Redis
* Add Laravel Boost as new possible package, enabled by default

**Full Changelog**: https://github.com/ngiraud/laravel-starter/compare/v1.0.6...v1.0.7

## Fix PHPStan error - 2025-09-05

**Full Changelog**: https://github.com/ngiraud/laravel-starter/compare/v1.0.5...v1.0.6

## Fix on AWS_URL for Minio - 2025-09-05

**Full Changelog**: https://github.com/ngiraud/laravel-starter/compare/v1.0.4...v1.0.5

## Update preset and chore - 2025-09-05

### What's new

- Remove final on each class
- Replace private visibility with protected on properties and methods
- Update pint.json to reflect my configuration

**Full Changelog**: https://github.com/ngiraud/laravel-starter/compare/v1.0.3...v1.0.4

## Refactoring the package with Claude - 2025-08-20

### What's Changed

* Refactoring the package with Claude by @ngiraud in https://github.com/ngiraud/laravel-starter/pull/3

### New Contributors

* @ngiraud made their first contribution in https://github.com/ngiraud/laravel-starter/pull/3

**Full Changelog**: https://github.com/ngiraud/laravel-starter/compare/v1.0.2...v1.0.3

## Finishing package - 2025-08-20

### What's new

- Copy web-local.php
- Modify User.php
- Modify TestCase.php
- Copy Github Actions
- Git the project
- Run Rector / Pint at the end
- Removing tests for now

**Full Changelog**: https://github.com/ngiraud/laravel-starter/compare/v1.0.1...v1.0.2

## Fix DB_DATABASE error - 2025-08-18

**Full Changelog**: https://github.com/ngiraud/laravel-starter/compare/v1.0.0...v1.0.1

## First release - 2025-08-18

**Full Changelog**: https://github.com/ngiraud/laravel-starter/commits/v1.0.0
