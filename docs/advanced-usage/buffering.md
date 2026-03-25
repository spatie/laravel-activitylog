---
title: Buffering activities
weight: 8
---

By default, each activity is saved to the database immediately with its own `INSERT` query. If your application logs many activities during a single request (for example, when updating multiple models in a loop), this can result in a significant number of queries.

When buffering is enabled, activities are collected in memory during the request and inserted in a single bulk query after the response has been sent to the client.

## When to use buffering

You should only enable buffering if your application logs a high volume of activities per request. For most applications that log just a handful of activities per request, the default behavior is perfectly fine.

Buffering is most useful when:

- You update many models in a single request (e.g., batch operations)
- You have endpoints that trigger a large number of model events
- You want to reduce database load from activity logging

## Enabling the buffer

Add this to your `.env` file:

```
ACTIVITYLOG_BUFFER_ENABLED=true
```

Or set it directly in `config/activitylog.php`:

```php
'buffer' => [
    'enabled' => true,
],
```

That's it. No other code changes are required. All existing logging code (both automatic model event logging and manual `activity()->log()` calls) will be buffered automatically.

## How it works

When buffering is enabled:

1. Activities are collected in an in-memory buffer instead of being saved immediately
2. After the response is sent to the client (during the `terminating` phase), all buffered activities are inserted in a single query
3. For queue workers, the buffer is flushed after each job completes

The buffer also registers a shutdown function as a safety net, so activities are flushed even if the application terminates unexpectedly.

## Things to be aware of

### No ID until flush

Buffered activities will not have a database ID until the buffer is flushed. If you need the activity ID immediately after logging, do not enable buffering.

```php
$activity = activity()->log('some activity');

// With buffering enabled, $activity->id will be null here.
// The activity will be saved after the response is sent.
```

### Works with Octane

The buffer is registered as a scoped binding, which means it is automatically reset between requests in Laravel Octane. The `terminating` callback fires per request in Octane, so activities are flushed correctly.

### Works with queues

The buffer is automatically flushed after each queued job completes (or fails). Activities logged within a job will be bulk inserted when that job finishes.
