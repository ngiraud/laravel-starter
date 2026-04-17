## Actions Pattern

- Controllers MUST delegate all business logic to Action classes
- Actions extend `App\Actions\Action` and implement a `handle()` method
- Actions live in `app/Actions/{Domain}/` (e.g., `app/Actions/Teams/CreateTeamAction.php`)
- Actions are singletons and use the `Fakeable` trait for testing
- Always inject actions via dependency injection in controller methods, not `Action::make()`
- Use **Verb + Noun** singular naming: `CreateTeamAction`, `SendMagicLinkAction`, `DeleteUserAction`
- Create separate actions for distinct operations
- Prefer DTOs over arrays for action input (e.g., `CreateTeamData`)

## Testing Actions

### Strategy

- Test functionality through Actions (unit tests), not through Controllers
- Use `Action::fake()` in feature tests to verify Controllers delegate to Actions
- Order tests: happy path first, edge cases, then error cases

### Action Delegation

```php
test('it delegates to CreateTeam action', function () {
    $user = User::factory()->create();

    CreateTeam::fake()
        ->shouldReceive('handle')
        ->once()
        ->with(
            Mockery::on(fn ($arg) => $arg->id === $user->id),
            Mockery::on(fn ($arg) => $arg['name'] === 'My Team')
        );
});
```
