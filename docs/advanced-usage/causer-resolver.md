---
title: Causer Resolver
weight: 11
---

The `CauserResolver` class handles resolving who caused an activity. It is registered as a scoped binding (per request) in the container.

For most use cases, you should use the `Activity` facade instead of interacting with `CauserResolver` directly:

```php
use Spatie\Activitylog\Facades\Activity;

// Scoped causer (recommended)
Activity::defaultCauser($admin, function () {
    // all activities here will have $admin as causer
});

// Global causer
Activity::defaultCauser($admin);
```

## Advanced usage

If you need lower-level control, resolve the `CauserResolver` from the container:

```php
use Spatie\Activitylog\Support\CauserResolver;

// Custom resolution callback
app(CauserResolver::class)->resolveUsing(function ($subject) {
    return User::find(1);
});
```

**Note:** `setCauser()` takes priority over `resolveUsing()`.

## Methods

### resolve

```php
public function resolve(Model | int | string | null $subject = null): ?Model;
```

### resolveUsing

```php
public function resolveUsing(Closure $callback): static;
```

### setCauser

```php
public function setCauser(?Model $causer): static;
```

### withCauser

```php
public function withCauser(?Model $causer, Closure $callback): mixed;
```
