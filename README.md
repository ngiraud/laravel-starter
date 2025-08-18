# An opinionated starter after creating a fresh Laravel application

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ngiraud/laravel-starter.svg?style=flat-square)](https://packagist.org/packages/ngiraud/laravel-starter)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/ngiraud/laravel-starter/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/ngiraud/laravel-starter/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/ngiraud/laravel-starter/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/ngiraud/laravel-starter/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/ngiraud/laravel-starter.svg?style=flat-square)](https://packagist.org/packages/ngiraud/laravel-starter)

This package automates the setup of a fresh Laravel application by installing and configuring the packages and tools you commonly use in your projects.

It configures Docker Compose with Laravel Sail, installs your preferred packages (Telescope, Horizon, Filament, etc.), sets up Composer scripts for development, and configures your
environment according to your preferences (locale, database, services).

## Installation

Install the package in your fresh Laravel application:

```bash
composer require ngiraud/laravel-starter
```

## Usage

After creating a new Laravel application with a starter kit, simply run:

```bash
php artisan starter:install
```

The command will guide you interactively to:

- **Configure environment**: application name, locale (fr/en), database settings
- **Choose Docker services**: MySQL, Redis, MinIO, etc.
- **Install Composer packages**: Laravel Telescope, Horizon, Filament, Larastan, Rector, etc.
- **Setup development scripts**: `composer dev`, `composer test`, `composer lint`
- **Publish configuration files**: pint.json, translation files
- **Publish custom AppServiceProvider**
- **Add a command to create an Action class**

### Available packages

- **Laravel Telescope** - Debugging and monitoring
- **Laravel Horizon** - Redis queue management
- **Filament** - Admin panel interface
- **Larastan** - Static analysis with PHPStan
- **Rector** - Automated refactoring
- **Laravel Backup** - Automated backups
- **Paratest** - Parallel testing
- **Laravel Nightwatch** - Monitoring application

### Added Composer scripts

```bash
composer dev      # Start all development services (logs, vite, queue)
composer dev:ssr  # Version with Inertia SSR
composer test     # Run tests with coverage
composer lint     # Code formatting with Pint
composer refactor # Refactoring with Rector
```

## Testing

```bash
composer test
```

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
