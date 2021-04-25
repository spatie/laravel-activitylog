---
title: Define causer for runtime
weight: 4
---

In many cases you may want to set causer globally maybe inside jobs where there's no logged user, v4 made this possible by introducing `CauserResolver` that will allow you to set the causer globally. See the example:

```php
// in a queue job or controller

use Spatie\Activitylog\Facade\CauserResolver;

// ... other code

$product = Product::first(1);
$causer = $product->owner;

CauserResover::setCauser($causer);

$product->update(['name' => 'New name']);

Activity::all()->last()->causer; // Product Model
Activity::all()->last()->causer->id; // 1

```

## Define Causer using callback

You can resolve causer using provided callback to `resolve` method
