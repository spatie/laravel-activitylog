---
title: Batch Logs
weight: 3
---

In some situations you may want to process multiple activities back to a single activity batch.

For example when a `User` deletes an `Author`, then that cascades soft deletes to the `Book`s that were owned by the `Author`. This way all modifications caused by that initial action are still associated with the same causer and batch UUID.

To start a new batch call `LogBatch::startBatch()` before any activity is done. Then all following actions will link back by UUID to that batch. After finishing activities you should end the batch by calling `LogBatch::endBatch()`.

Here's an example:

```php
use Spatie\Activitylog\Facades\LogBatch;
use Spatie\Activitylog\Models\Activity;

LogBatch::startBatch();
$author = Author::create(['name' => 'Philip K. Dick']);
$book = Book::create(['name' => 'A Scanner Brightly', 'author_id' => $author->id]);
$book->update(['name' => 'A Scanner Darkly']);
$author->delete();
LogBatch::endBatch();
```

Doing this would allow all the activities within this batch to log together as described. This helps ensure those non-explicit actions like the cascade delete of the book get captured too.

## Retrieve Activities by batch

Once the batch is closed, if you save the batch's UUID, then you can retrieve all activities related to that batch. Do this by using the `Activity::whereBatchUuid($batchUuid)` lookup scope.

For example:

```php
// ... started batch and other code
$batchUuid = LogBatch::getUuid(); // save batch id to retrieve activities later
LogBatch::endBatch();

$batchActivities = Activity::forBatch($batchUuid)->get();
```

Example results:

**Note** that in the examples both `Author` and `Book` are implementing `LogsActivity` trait.

```php
use Spatie\Activitylog\Facades\LogBatch;
use Spatie\Activitylog\Models\Activity;

LogBatch::startBatch();

$author = Author::create(['name' => 'Philip K. Dick']);
$book = Book::create(['name' => 'A Scanner Brightly', 'author_id' => $author->id]);
$book->update(['name' => 'A Scanner Darkly']);
$book2 = Book::create(['name' => 'Paycheck', 'author_id' => $author->id]);

$author->delete();

$batchUuid = LogBatch::getUuid(); // save batch id to retrieve activities later
LogBatch::endBatch();

$batchActivities = Activity::forBatch($batchUuid)->get();
var_dump($batchActivities); // A collection of Activity models...
// They will be in order: Author;created, Book;created, Book;updated,
//      Book;created, Author;deleted, Book;deleted and Book;deleted
```

## Note on starting new batches

You may **not** start a new batch before ending the current open batch. Opening new batch while another one is open in the same request will result in the same `uuid`.

Activity batches works similarly to database transactions where number of started transactions/batches must be equal to the number of committed/closed transactions/batches.

## Check if batch is open

It's important to not open a new batch from within an existing batch. This type of thing may come up within a queue job or middleware.

To verify if a batch is open or closed you can do the following:

```php
// in middleware
LogBatch::startBatch();

//... Other middlewares

if(LogBatch::isOpen()) {
    // do something
}

```

## Keep LogBatch openend during multiple job/requests

In some cases when you have multiple jobs that goes through queue batch, and you want to log all the activities during these different jobs using the same `LogBatch`, or you want to log multiple activities throughout multiple requests.

You may utilize `LogBatch::setBatch($uuid)` passing `$uuid` or any unique value that identify the batch to keep it open.

Here's an example:

```php
use Spatie\Activitylog\Facades\LogBatch;
use Illuminate\Bus\Batch;
use Illuminate\Support\Str;

$uuid =  Str::uuid();

Bus::batch([
    // First job will open a batch
    new SomeJob('some value', $uuid), // pass uuid as a payload to the job
    new AnotherJob($uuid), // pass uuid as a payload to the job
    new WorkJob('work work work', $uuid), // pass uuid as a payload to the job
])->then(function (Batch $batch) {
    // All jobs completed successfully...
})->catch(function (Batch $batch, Throwable $e) {
    // First batch job failure detected...
})->finally(function (Batch $batch) use ($uuid) {
    // The batch has finished executing...
    LogBatch::getUuid() === $uuid // true
    LogBatch::endBatch();
})->dispatch();

// Later on..
Activity::forBatch($uuid)->get(); // all the activity that happend in the batch

```

```php
class SomeJob
{
    public function handle(string $value, ?string $batchUuid = null)
    {
        LogBatch::startBatch();
        if($batchUuid) LogBatch::setBatch($batchUuid);

        // other code ..
    }
}

```

## Batch activities using callback

You can also batch activities using closure passed to `LogBatch::withinBatch()`. Every activity executed will happen inside that closure will be included in the same batch.

Here's an example:

```php
use Spatie\Activitylog\Facades\LogBatch;

LogBatch::withinBatch(function(string $uuid) {
    $uuid; // 5cce9cb3-3144-4d35-9015-830cf0f20691
    activity()->log('my message');
    $item = NewsItem::create(['name' => 'new batch']);
    $item->update(['name' => 'updated']);
    $item->delete();
});

Activity::latest()->get(); // batch_uuid: 5cce9cb3-3144-4d35-9015-830cf0f20691

```
