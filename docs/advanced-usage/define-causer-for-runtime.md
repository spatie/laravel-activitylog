---
title: Define causer for runtime
weight: 4
---

In many cases you may want to set the causer globally, for example inside jobs where there's no logged-in user. The `CauserResolver` allows you to do this.

The recommended approach is `withCauser()`, which scopes the causer to a callback and automatically restores the previous causer afterwards:

```php
use Spatie\Activitylog\Facades\CauserResolver;

$product = Product::find(1);
$causer = $product->owner;

CauserResolver::withCauser($causer, function () use ($product) {
    $product->update(['name' => 'New name']);
});

Activity::all()->last()->causer; // the product owner
```

This works cleanly in jobs, CLI commands, seeders, and multi-guard setups.

## Setting causer globally

If you need to set the causer for the entire request (without scoping), use `setCauser()`:

```php
CauserResolver::setCauser($causer);

$product->update(['name' => 'New name']);

Activity::all()->last()->causer; // $causer
```

## Define causer using callback

You can resolve the causer using a custom callback with the `resolveUsing()` method:

```php
CauserResolver::resolveUsing(function ($subject) {
    return User::find(1);
});
```
