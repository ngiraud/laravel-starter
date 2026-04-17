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

## Finalization

- Before considering a feature complete, run `composer test:all`
- This command runs: lint (Pint + Rector dry-run + ESLint + Prettier), static analysis (PHPStan), tests (Pest)
- Do not commit if this command fails
