## Enums

- Enums that need frontend serialization use the `EnhanceEnum` trait (`app/Enums/Concerns/EnhanceEnum.php`)
- Enums using `EnhanceEnum` MUST implement a `label(): string` method — the trait requires it
- The trait provides `toArray()`, `collect()`, `options()` for free
- Use `options()` to pass enum choices to Vue/Inertia
