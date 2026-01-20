## Eloquent

- Never use `$fillable` or `$guarded` properties. We call `Model::unguard()` in AppServiceProvider and prefer application-wide unguarding.
