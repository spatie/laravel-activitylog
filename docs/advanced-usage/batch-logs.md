---
title: Batch Logs
weight: 2
---

In some cases you may want to link/batch multiple activities back to a single activity. For example User deletes an author, then that cascades soft deletes to the books for that author.

You can start new batch by calling `LogBatch::startBatch()` before any activity is done then all of following activities will link back by UUID to that batch. After finishing activities you should end the batch `LogBatch::endBatch()`.

```php
use Spatie\Activitylog\Facades\LogBatch;
use Spatie\Activitylog\Models\Activity;

LogBatch::startBatch();
$article = NewsItem::create(['name' => 'new article']);
$article->update(['name' => 'update article']);
$article->delete();

$batchUuid = LogBatch::getUuid(); // save batch id to retrive activities later
LogBatch::endBatch();

Activity::forBatch($batchUuid)->get(); // collection of 3 activity models ['created', 'updated', 'deleted']
```

You can now retrive all activities related to a single batch by using `Activity::forBatch($batchUuid);` scope.

## Check if batch is open

During any batch you may want to check if the batch is open or not, that would be useful in queue job or middleware.

```php
// in middleware
LogBatch::openBatch();

//... Other middlewares

if(LogBatch::isOpen()) {
    // do something
}

```

## Batch activities using callback

If you feel like it, you can batch activities using closure passed to `LogBatch::withinBatch()`, every activity will happen inside that closure will be assigned to the same batch uuid.

Here's an example:

```php
use Spatie\Activitylog\LogBatch;

LogBatch::withinBatch(function($uuid) {
    activity()->log('my message');
    $item = NewsItem::create(['name' => 'new batch']);
    $item->update(['name' => 'updated']);
    $item->delete();
});

```

## Retrive Activities by batch

You can get all activities that happend in a single batch using `Activity::forBatch($batchUuid)` scope or check if the `Activity::hasBatch()`.
