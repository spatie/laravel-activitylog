---
title: Cleaning up the log
weight: 2
---

After using the package for a while you might have recorded a lot of activity. This package provides an artisan command `activitylog:clean` to clean the log.

Running this command will result in the deletion of all recorded activity that is older than the number of days specified in the `delete_records_older_than_days` of the config file.

You can leverage Laravel's scheduler to run the clean up command now and then.

```php
//app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
   $schedule->command('activitylog:clean')->daily();
}
```
