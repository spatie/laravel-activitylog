---
title: Log Batch
weight: 2
---

This class registered as singleton and allows you to batch/group activities.

```php
    LogBatch::startBatch();

    LogBatch::getUuid(); // 15c72460-4998-49ac-a0a3-647cc6f312ef

    $log = activity()->log('log look mom, I did something...');
    $author = Author::first();
    $author->delete(); // deletes books too

    Activity::latest()->get();
    // Author Deleted,  batch_uuid: 15c72460-4998-49ac-a0a3-647cc6f312ef
    // Book 1 Deleted,  batch_uuid: 15c72460-4998-49ac-a0a3-647cc6f312ef
    // Book 2 Deleted,  batch_uuid: 15c72460-4998-49ac-a0a3-647cc6f312ef
    // log look mom, I did something..., batch_uuid: 15c72460-4998-49ac-a0a3-647cc6f312ef
    $log->batch_uuid; // 15c72460-4998-49ac-a0a3-647cc6f312ef

    LogBatch::endBatch();

```

## startBatch

```php
   public function startBatch(): void;
```

## isOpen

```php
    /**
     * Check if there's an open batch
     */
    public function isOpen(): bool
```

## endBatch

```php
     public function endBatch(): void;
```

## withinBatch

```php
    /**
     * Start new batch, execute the callback passed in uuid, end the batch.
     */
     public function withinBatch(Closure $callback): mixed;
```
