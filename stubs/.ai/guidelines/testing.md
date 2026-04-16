# Testing Guidelines

## Test Organization

- Name test classes by **action**, not by controller: `UpdateTeamTest` not `TeamControllerTest`
- Organize tests by domain using **singular** naming: `tests/Feature/Team/`, `tests/Feature/Billing/`
- Group related controller methods in the same test file
- Name `describe()` blocks using **action-centric** names: `describe('update team', ...)`

## Test Groups

- Always define `pest()->group()` **after** `use` imports, before the first test
- Use **technical layer** groups: `actions`, `models`, `jobs`, `policies`, `controllers`, `commands`, `middleware`
- Use **business domain** groups: `billing`, `auth`, `team`, `user`, `subscription`, `api`

```php
use App\Models\Team;

pest()->group('controllers', 'team');

test('user can update team name', function () { ... });
```

## TestCase Configuration

- `RefreshDatabase` is in the base `TestCase` - never add it in individual tests
- Prefer Pest's `expect()` over PHPUnit's `$this->assert*()`
- Always use model factories; check for custom states before manually setting attributes

## Testing Strategy

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
