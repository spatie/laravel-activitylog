---
title: Cleaning up the log
weight: 2
---

After using the package for a while you might have recorded a lot of activity. This package provides an artisan command `activitylog:clean` to clean the log.

Running this command will result in the deletion of all recorded activity that is older than the number of days specified in the `delete_records_older_than_days` of the config file.

You can leverage Laravel's scheduler to run the clean up command now and then.

```bash
php artisan activitylog:clean
```

```php
//app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
   $schedule->command('activitylog:clean')->daily();
}
```

If you want to automatically cleanup your `production` system you should append the `--force` option as the command will otherwise ask you to confirm the action. This is to prevent accidental data loss.


```php
// routes/console.php (laravel 12)

use Spatie\Activitylog\CleanActivitylogCommand;

Schedule::command(CleanActivitylogCommand::class, ['--force'])->daily();

// app/Console/Kernel.php (for Laravel 12 and below)

use Spatie\Activitylog\CleanActivitylogCommand;

protected function schedule(Schedule $schedule)
{
    $schedule->command(CleanActivitylogCommand::class, ['--force'])->daily();
}

```

## Define the log to clean

If you want to clean just one log you can define it as command argument. It will filter the `log_name` attribute of the `Activity` model.

```bash
php artisan activitylog:clean my_log_channel
```

## Overwrite the days to keep per call

You can define the days to keep for each call as command option. This will overwrite the config for this run.

```bash
php artisan activitylog:clean --days=7
```

## MySQL - Rebuild index & get back space after clean.

After clean, you might experience database table size still allocated more than actual lines in table,
execute this line in MySQL to OPTIMIZE / ANALYZE table.

```bash
OPTIMIZE TABLE activity_log;
```
OR
```bash
ANALYZE TABLE activity_log;
```

*this SQL operation will lock write/read of database, use ONLY when server under maintanance mode.
