---
title: Causer Resolver
weight: 3
---

This class is registered as scoped (per request) and will allow you to set the causer for activity globally or on a per action basis.

**Note that** overriding causer using `setCauser` method takes priority over overriding the resolver using `resolveUsing` method.

```php
CauserResolver::setCauser(User::find(1));

$log = activity()->log('log look mom, I did something...');
$log->causer; // User Model with id of 1
```

## withCauser

The recommended way to override the causer for a block of code is to use `withCauser()`. This scopes the causer to the callback and automatically restores the previous causer afterwards. This is useful in jobs, CLI commands, seeders, and multi-guard setups.

```php
use Spatie\Activitylog\Facades\CauserResolver;

CauserResolver::withCauser($admin, function () {
    // all activities logged here will have $admin as the causer

    $article->update(['title' => 'New title']);
    $article->tags()->sync([1, 2, 3]);
});

// the previous causer (or null) is restored here
```

## resolve

```php
/**
 * Resolve causer based different arguments. First checks for override causer,
 * then override closure, then falls back to the authenticated user.
 */
public function resolve(Model | int | string | null $subject = null): ?Model;
```

## resolveUsing

```php
/**
 * Override the resolver using callback.
 */
public function resolveUsing(Closure $callback): static;
```

## setCauser

```php
/**
 * Override default causer.
 */
public function setCauser(?Model $causer): static;
```
