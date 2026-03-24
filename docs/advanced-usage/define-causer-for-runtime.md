---
title: Define causer for runtime
weight: 4
---

In many cases you may want to set the causer for a block of code, for example inside jobs where there's no logged-in user. The `Activity` facade provides `defaultCauser()` for this.

## Scoped causer

Pass a callback as the second argument to scope the causer to that block. The previous causer is automatically restored afterwards:

```php
use Spatie\Activitylog\Facades\Activity;

Activity::defaultCauser($admin, function () {
    $product->update(['name' => 'New name']);
    // this activity will have $admin as the causer
});

// the previous causer is restored here
```

This works cleanly in jobs, CLI commands, seeders, and multi-guard setups.

## Global causer

Without a callback, the causer is set for the rest of the request:

```php
use Spatie\Activitylog\Facades\Activity;

Activity::defaultCauser($admin);

$product->update(['name' => 'New name']);

\Spatie\Activitylog\Models\Activity::all()->last()->causer; // $admin
```
