# An opinionated starter to launch after creating a fresh Laravel application

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ngiraud/laravel-starter.svg?style=flat-square)](https://packagist.org/packages/ngiraud/laravel-starter)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/ngiraud/laravel-starter/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/ngiraud/laravel-starter/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/ngiraud/laravel-starter/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/ngiraud/laravel-starter/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/ngiraud/laravel-starter.svg?style=flat-square)](https://packagist.org/packages/ngiraud/laravel-starter)

This package automates the complete setup of a fresh Laravel application by installing and configuring the packages and tools you commonly use in your projects, with full Git management throughout the process.

It configures Docker Compose with Laravel Sail, installs your preferred packages (Telescope, Horizon, Filament, etc.), sets up Composer scripts for development, publishes configuration files and stubs, configures your environment according to your preferences (locale, database, services), and automatically creates semantic Git commits for each step.

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

The command will guide you interactively through the complete setup process:

## 🔧 Environment Configuration
- **Application settings**: name, locale (fr/en), database configuration
- **Docker services**: MySQL, Redis, MinIO, Mailpit, Selenium, and more
- **Git repository**: automatic initialization and semantic commits throughout

## 📦 Package Management  
- **Install Composer packages**: Laravel Telescope, Horizon, Filament, Larastan, Rector, etc.
- **Frontend dependencies**: automatic npm install via Sail
- **Service dependencies**: automatic installation (e.g., AWS S3 for MinIO)

## 🛠️ Development Environment
- **Composer scripts**: `composer dev`, `composer test`, `composer lint`, `composer refactor`
- **Configuration files**: Pint, PHPStan, Rector configurations
- **GitHub Actions**: CI/CD workflows with proper service dependencies
- **Code quality**: automatic Rector and Pint formatting at the end

## 📁 Project Structure
- **Custom stubs**: AppServiceProvider, User model, TestCase
- **Route management**: web-local.php for local development
- **Language files**: French translations if selected
- **Action command**: `php artisan make:action` for creating Action classes

## 📋 Available Packages

The installer offers these carefully selected packages:

- **🔭 Laravel Telescope** - Debugging and monitoring dashboard
- **⏱️ Laravel Horizon** - Redis queue management and monitoring  
- **🎛️ Filament** - Modern admin panel framework
- **🔍 Larastan** - Static analysis with PHPStan for Laravel
- **🔄 Rector** - Automated code refactoring and modernization
- **💾 Laravel Backup** - Database and file backup automation
- **⚡ Paratest** - Parallel test execution for faster testing
- **👀 Laravel Nightwatch** - Application monitoring and alerting

## ⚡ Development Scripts

The installer automatically adds these convenient Composer scripts:

```bash
composer dev      # Start all development services (logs, vite, queue)
composer dev:ssr  # Version with Inertia SSR support
composer test     # Run tests with coverage reporting
composer lint     # Code formatting with Laravel Pint
composer refactor # Automated refactoring with Rector
```

## 🚀 Installation Process

The package follows a comprehensive workflow:

1. **Prerequisites check** - Ensures Laravel Sail is installed
2. **User preferences** - Interactive prompts for services and packages  
3. **Git initialization** - Sets up repository with initial commit
4. **Environment setup** - Updates .env and .env.example files
5. **Sail installation** - Configures Docker services
6. **Package installation** - Installs selected Composer packages with individual commits
7. **File publishing** - Publishes stubs, configurations, and GitHub Actions
8. **Database migration** - Sets up initial database schema
9. **Code optimization** - Applies Rector and Pint formatting rules
10. **Completion** - Provides next steps for development

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
