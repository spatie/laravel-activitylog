---
title: Disabling logging
weight: 6
---

You can disable all logging globally for the current request by calling

```php
activity()->disableLogging();
```

If you want to enable logging again call `activity()->enableLogging()`.

## Without logging

If you want to run a given code snippet without logs you can use the `withoutLogging()` method.

```php
activity()->withoutLogging(function () {
    // ...
});
```

Everything that would produce an activitylog (model events, explicit calls) won't save an activity.

To disable logging for a specific model instance instead of globally, see the [per-model disabling](/docs/laravel-activitylog/v5/advanced-usage/logging-model-events#disabling-logging-on-demand) section.
