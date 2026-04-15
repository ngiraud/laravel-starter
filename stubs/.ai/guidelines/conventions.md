## Documentation

- Every documentated code should be exclusively in English.

## Namespace Imports

- Always import classes with `use` statements at the top of the file
- Never use fully qualified class names (FQCN) in the code body or docblocks

## Eloquent

- Never use `$fillable` or `$guarded` properties. We call `Model::unguard()` in AppServiceProvider and prefer application-wide unguarding.
- Always use the `#[Scope]` attribute on protected methods instead of the `scope` prefix convention

## Eloquent API Resources

- Use `Resource::make()` instead of `new Resource()` in Controllers
- Always pass models to Vue/Inertia via API Resources - never pass raw Eloquent models

## Form Requests

- Always use array syntax for validation rules, not pipe-delimited strings
- Use `Rule::` classes for complex validations

## Authorization

- Define authorization in route definitions using `->can()`, not in controllers or Form Requests

## Actions Pattern

- Controllers MUST delegate all business logic to Action classes
- Actions extend `App\Actions\Action` and implement a `handle()` method
- Actions live in `app/Actions/{Domain}/` (e.g., `app/Actions/Teams/CreateTeamAction.php`)
- Actions are singletons and use the `Fakeable` trait for testing
- Always inject actions via dependency injection in controller methods, not `Action::make()`
- Use **Verb + Noun** singular naming: `CreateTeamAction`, `SendMagicLinkAction`, `DeleteUserAction`
- Create separate actions for distinct operations
- Prefer DTOs over arrays for action input (e.g., `CreateTeamData`)

## Controllers

- Never use try/catch in controllers - let Laravel's exception handler deal with exceptions
- For custom error responses, create custom exception classes

## Inertia Flash Messages

- Use `Inertia::flash()` for one-time notifications
- Separate `Inertia::flash()` call from `return` statement for better type safety

## Attributes

- Use Contextual Attributes whenever possible: `#[CurrentUser]`, etc.

## DTOs

- DTOs live in `app/Data/` (e.g., `app/Data/ProjectData.php`)
- Use readonly constructor property promotion: `public readonly string $name`
- DTOs are plain PHP objects — no Spatie Data dependency

## Enums

- Enums that need frontend serialization use the `EnhanceEnum` trait (`app/Enums/Concerns/EnhanceEnum.php`)
- Enums using `EnhanceEnum` MUST implement a `label(): string` method — the trait requires it
- The trait provides `toArray()`, `collect()`, `options()` for free
- Use `options()` to pass enum choices to Vue/Inertia

## Finalization

- Before considering a feature complete, run `composer test:all`
- This command runs: lint (Pint + Rector dry-run + ESLint + Prettier), static analysis (PHPStan), tests (Pest)
- Do not commit if this command fails
