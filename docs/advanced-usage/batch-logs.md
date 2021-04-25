---
title: Batch Logs
weight: 3
---

In some cases you may want to link multiple activities back to a single activity that started it all. For example author was deleted, that cascades soft deletes to the books for that author.
Now the deleted author and deleted books are now having the same `uuid`.

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

## Note on starting new batches

You may **not** start a new batch before ending the current open batch. Opening new batch while another one is open in the same request will result in the same `uuid`.

Activity batches works similarly to database transactions where number of started transactions/batches must be equal to the number of committed/closed transactions/batches.

## Check if batch is open

During any batch you can check if the batch is open or not, that would be useful in queue job or middleware.

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

LogBatch::withinBatch(function(string $uuid) {
    $uuid; // 5cce9cb3-3144-4d35-9015-830cf0f20691
    activity()->log('my message');
    $item = NewsItem::create(['name' => 'new batch']);
    $item->update(['name' => 'updated']);
    $item->delete();
});

Activity::latest()->get(); // batch_uuid: 5cce9cb3-3144-4d35-9015-830cf0f20691

```

## Retrive Activities by batch

You can get all activities that happend in a single batch using `Activity::forBatch($batchUuid)` scope or check if the `Activity::hasBatch()`.
