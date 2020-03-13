---
title: Disabling logging
weight: 4
---

You can disable all logging activities in the current request by calling

```php
activity()->disableLogging();
```

If you want to enable logging again call `activity()->enableLogging()`.

## without Logs

If you want to run a given code snippet without logs you can use the `withoutLogs()` method. 

```php
activity()->withoutLogs(function(){
    // ...
});
```

Everything that would produce an activitylog (model events, explicit calls) won't save an activity.
