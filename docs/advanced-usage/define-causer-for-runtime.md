---
title: Define causer for runtime
weight: 4
---

In many cases you may want to set causer globally maybe inside jobs where there's no logged-in user, v4 made this possible by introducing `CauserResolver` that will allow you to set the causer globally. See the example:

```php
// in a queue job or controller

use Spatie\Activitylog\Facades\CauserResolver;

// ... other code

$product = Product::first(1);
$causer = $product->owner;

CauserResolver::setCauser($causer);

$product->update(['name' => 'New name']);

Activity::latest()->first()->causer; // Product Model
Activity::latest()->first()->causer->id; // Product#1 Owner

```

## Define Causer using callback

You can resolve causer using provided callback to `resolve` method
