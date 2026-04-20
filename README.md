# An opinionated starter to launch after creating a fresh Laravel application

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ngiraud/laravel-starter.svg?style=flat-square)](https://packagist.org/packages/ngiraud/laravel-starter)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/ngiraud/laravel-starter/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/ngiraud/laravel-starter/actions?query=workflow%3ATests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/ngiraud/laravel-starter/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/ngiraud/laravel-starter/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/ngiraud/laravel-starter.svg?style=flat-square)](https://packagist.org/packages/ngiraud/laravel-starter)

This package automates the complete setup of a fresh Laravel application: installing and configuring your preferred packages and tools, with full Git management throughout the process.

It configures Docker Compose with Laravel Sail, installs your preferred packages (Telescope, Horizon, Filament, etc.), sets up Composer and npm scripts for development, publishes configuration files and stubs, configures your environment (locale, database, services), and automatically creates semantic Git commits for each step.

## Installation

```bash
composer require ngiraud/laravel-starter --dev
```

## Usage

### Full setup

Run the interactive installer on a fresh Laravel application:

```bash
php artisan starter:install
```

It will guide you through the complete setup and delegate to the sub-commands below. At the end it offers to remove itself from your project.

### Individual commands

Each step is also available as a standalone command, usable at any time after initial setup:

| Command | Description |
|---|---|
| `starter:init` | `git init` + `.env` configuration (name, locale, database) |
| `starter:add {package}` | Install a package + post-install steps + commit |
| `starter:remove {package}` | Remove a package + cleanup + commit |
| `starter:publish` | Publish config stubs, scripts, GitHub Actions, and opt-in extras |
| `starter:finalize` | Run `composer lint` (Rector + Pint) and commit |

## Available packages

| Key | Package | Default |
|---|---|---|
| `telescope` | Laravel Telescope | ✓ |
| `horizon` | Laravel Horizon | — |
| `filament` | Filament | — |
| `larastan` | Larastan | ✓ |
| `rector` | Rector (Laravel) | ✓ |
| `backup` | Laravel Backup | — |
| `paratest` | Paratest | — |
| `nightwatch` | Laravel Nightwatch | — |

## What gets published

`starter:publish` sets up the following, with opt-in prompts for extras:

- **Config files**: `pint.json`, `AppServiceProvider.php`, `TestCase.php`
- **Routes**: `web-local.php` (local-only routes, auto-required in `web.php`)
- **GitHub Actions**: tests, lint, phpstan (if Larastan), rector (if Rector)
- **Language files**: French translations if locale is `fr`
- **Composer scripts**: `dev`, `lint`, `test`, `test:lint`, `test:types`, `test:all`
- **npm scripts**: `dev`, `lint`, `test:lint`
- **`.gitignore`**: adds `/.claude` entry
- **AI guidelines** *(opt-in)*: `.ai/guidelines/` stubs for testing and conventions
- **Action design pattern** *(opt-in)*: `Action`, `Fakeable`, `FakeAction`, `FakeableTest`, `MakeActionCommand` + `make:action` stub
- **EnhanceEnum trait** *(opt-in)*: `app/Enums/Concerns/EnhanceEnum.php`

## Development scripts

After running the installer, these scripts are available in your project:

```bash
composer dev        # Start all dev services concurrently (logs, vite, queue)
composer lint       # Rector + Pint + ESLint
composer test       # Run the test suite
composer test:lint  # Dry-run lint (CI)
composer test:types # PHPStan (if Larastan installed)
composer test:all   # Full CI suite
```

## Testing

```bash
composer test
```

## Local development

Two helper scripts are provided to wire up the package as a local path repository in a target project:

```bash
# Add path repo + minimum-stability: dev to the project's composer.json
./scripts/setup-local.sh my-laravel-project

# Mount the package into the project's Sail compose file
./scripts/setup-sail.sh my-laravel-project
```

Both scripts resolve paths automatically relative to their own location (assumes projects live alongside `laravel-packages/` in the same parent directory).

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Nicolas Giraud](https://github.com/ngiraud)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
