<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Actions\LogActivityAction;
use Spatie\Activitylog\Models\Activity as DefaultActivity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\ActivityBuffer;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Test\Models\Article;
use Spatie\Activitylog\Test\Models\CustomDatabaseConnectionOnActivityModel as Activity;

beforeEach(function () {
    config([
        'database.connections.custom_connection_name' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ],
        'activitylog.activity_model' => Activity::class,
    ]);

    DB::setDefaultConnection('custom_connection_name');
    $this->migrateActivityLogTable();
    DB::setDefaultConnection('sqlite');
});

afterEach(function () {
    LogActivityAction::clearBeforeLoggingCallbacks();
});

it('discards activities when the subject transaction is rolled back', function (bool $buffered) {
    config(['activitylog.buffer.enabled' => $buffered]);

    try {
        DB::transaction(function () {
            $article = Article::create(['name' => 'Rolled back']);
            activity()->performedOn($article)->log('created');

            throw new RuntimeException('Roll back');
        });
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('Roll back');
    }

    app(ActivityBuffer::class)->flush();

    expect(Article::where('name', 'Rolled back')->exists())->toBeFalse();
    expect(Activity::count())->toBe(0);
})->with([false, true]);

it('saves an activity only after the outermost subject transaction commits', function (bool $buffered) {
    config(['activitylog.buffer.enabled' => $buffered]);
    DB::beginTransaction();
    DB::beginTransaction();
    $article = Article::create(['name' => 'Committed']);

    $activity = activity()->performedOn($article)->log('created');
    DB::commit();
    app(ActivityBuffer::class)->flush();

    expect(Activity::count())->toBe(0);
    expect($activity->exists)->toBeFalse();
    expect($activity->getKey())->toBeNull();

    DB::commit();
    app(ActivityBuffer::class)->flush();

    $saved = Activity::sole();
    expect($saved->description)->toBe('created');
    expect($saved->subject_id)->toBe($article->id);
    expect(Article::find($article->id)->name)->toBe('Committed');

    if (! $buffered) {
        expect($activity->exists)->toBeTrue();
        expect($activity->getKey())->toBe($saved->getKey());
    }
})->with([false, true]);

it('discards a committed savepoint when its outer transaction rolls back', function () {
    DB::beginTransaction();
    DB::beginTransaction();
    $article = Article::create(['name' => 'Rolled back']);
    activity()->performedOn($article)->log('created');
    DB::commit();
    DB::rollBack();

    expect(Article::find($article->id))->toBeNull();
    expect(Activity::count())->toBe(0);
});

it('discards a rolled back savepoint without discarding the outer activity', function () {
    $article = Article::first();
    DB::beginTransaction();
    $article->update(['name' => 'Outer']);
    activity()->performedOn($article)->log('outer');
    DB::beginTransaction();
    $article->update(['name' => 'Inner']);
    activity()->performedOn($article)->log('inner');
    DB::rollBack();
    DB::commit();

    expect($article->fresh()->name)->toBe('Outer');
    expect(Activity::sole()->description)->toBe('outer');
});

it('discards committed descendants of a rolled back savepoint', function () {
    $article = Article::first();
    DB::beginTransaction();
    activity()->performedOn($article)->log('outer');
    DB::beginTransaction();
    DB::beginTransaction();
    activity()->performedOn($article)->log('inner');
    DB::commit();
    DB::rollBack();
    DB::commit();

    expect(Activity::sole()->description)->toBe('outer');
});

it('waits for the subject connection when another connection commits first', function () {
    $article = Article::first();
    DB::beginTransaction();
    $audit = DB::connection('custom_connection_name');
    $audit->beginTransaction();

    activity()->performedOn($article)->log('rolled back');
    $audit->commit();

    expect(Activity::count())->toBe(0);

    DB::rollBack();

    expect(Activity::count())->toBe(0);
});

it('saves on the subject commit while an unrelated transaction remains open', function () {
    config(['database.connections.unrelated' => config('database.connections.sqlite')]);
    $article = Article::first();
    DB::beginTransaction();
    $unrelated = DB::connection('unrelated');
    $unrelated->beginTransaction();

    activity()->performedOn($article)->log('committed');
    DB::commit();

    expect(Activity::sole()->description)->toBe('committed');

    $unrelated->rollBack();

    expect(Activity::count())->toBe(1);
});

it('uses the subject connection when it is not the default connection', function () {
    $article = Article::first()->setConnection('sqlite');
    $source = $article->getConnection();
    DB::setDefaultConnection('custom_connection_name');
    $source->beginTransaction();

    $article->update(['name' => 'Rolled back']);
    activity()->performedOn($article)->log('rolled back');
    $source->rollBack();

    expect($article->fresh()->name)->not->toBe('Rolled back');
    expect(Activity::count())->toBe(0);
});

it('keeps unbuffered activities on the same connection inside the subject transaction', function () {
    config(['activitylog.activity_model' => DefaultActivity::class]);
    DB::beginTransaction();
    $article = Article::create(['name' => 'Rolled back']);

    $activity = activity()->performedOn($article)->log('created');

    expect($activity->exists)->toBeTrue();
    expect(DefaultActivity::count())->toBe(1);

    DB::rollBack();

    expect(Article::find($article->id))->toBeNull();
    expect(DefaultActivity::count())->toBe(0);
});

it('does not buffer rolled back activities even on the same connection', function () {
    config([
        'activitylog.activity_model' => DefaultActivity::class,
        'activitylog.buffer.enabled' => true,
    ]);
    DB::beginTransaction();
    activity()->performedOn(Article::first())->log('rolled back');
    DB::rollBack();
    app(ActivityBuffer::class)->flush();

    expect(DefaultActivity::count())->toBe(0);
});

it('saves immediately when the subject is not in a transaction', function () {
    $activity = activity()->performedOn(Article::first())->log('immediate');

    expect($activity->exists)->toBeTrue();
    expect(Activity::sole()->id)->toBe($activity->id);
});

it('does not infer a subject transaction for activities without a subject', function () {
    DB::beginTransaction();
    $activity = activity()->log('attempted operation');
    DB::rollBack();

    expect($activity->exists)->toBeTrue();
    expect(Activity::sole()->description)->toBe('attempted operation');
});

it('captures attributes and timestamps after hooks run instead of at commit time', function (bool $buffered) {
    config(['activitylog.buffer.enabled' => $buffered]);
    $this->freezeTime();
    $loggedAt = now()->toDateTimeString();
    $hookCalls = 0;
    LogActivityAction::beforeLogging(function ($activity) use (&$hookCalls) {
        $hookCalls++;
        $activity->properties = $activity->properties->put('source', 'request');
    });
    DB::beginTransaction();

    $activity = activity()
        ->performedOn(Article::first())
        ->withChanges(['attributes' => ['name' => 'Original']])
        ->log('original');
    $activity->description = 'mutated';
    $activity->properties = $activity->properties->put('source', 'mutated');
    $activity->attribute_changes = ['attributes' => ['name' => 'Mutated']];
    $this->travel(1)->minute();
    DB::commit();
    app(ActivityBuffer::class)->flush();

    $saved = Activity::sole();
    expect($saved->description)->toBe('original');
    expect($saved->properties->all())->toBe(['source' => 'request']);
    expect($saved->attribute_changes->all())->toBe(['attributes' => ['name' => 'Original']]);
    expect($saved->created_at->toDateTimeString())->toBe($loggedAt);
    expect($hookCalls)->toBe(1);
})->with([false, true]);

it('discards automatic model event logs after rollback', function (string $event) {
    $article = new class extends Article
    {
        use LogsActivity;

        public function getActivitylogOptions(): LogOptions
        {
            return LogOptions::defaults()->logAll();
        }
    };
    $article->disableLogging();
    $article->name = 'Original';
    $article->save();
    $article->enableLogging();
    DB::beginTransaction();

    match ($event) {
        'created' => $article->replicate()->save(),
        'updated' => $article->update(['name' => 'Updated']),
        'deleted' => $article->delete(),
    };
    DB::rollBack();

    expect(Article::find($article->id)->name)->toBe('Original');
    expect(Activity::count())->toBe(0);
})->with(['created', 'updated', 'deleted']);

it('preserves logging order across committed and rolled back savepoints', function (bool $buffered) {
    config(['activitylog.buffer.enabled' => $buffered]);
    $this->freezeTime();
    $article = Article::first();
    DB::beginTransaction();
    activity()->performedOn($article)->log('first');
    DB::beginTransaction();
    activity()->performedOn($article)->log('second');
    DB::commit();
    DB::beginTransaction();
    activity()->performedOn($article)->log('discarded');
    DB::rollBack();
    activity()->performedOn($article)->log('third');
    DB::commit();
    app(ActivityBuffer::class)->flush();

    expect(Activity::orderBy('id')->pluck('description')->all())->toBe(['first', 'second', 'third']);
})->with([false, true]);

it('leaves the subject committed if saving to the activity database fails', function () {
    DB::connection('custom_connection_name')->getSchemaBuilder()->drop('activity_log');

    expect(fn () => DB::transaction(function () {
        $article = Article::create(['name' => 'Committed without activity']);
        activity()->performedOn($article)->log('created');
    }))->toThrow(QueryException::class);

    expect(Article::where('name', 'Committed without activity')->count())->toBe(1);
});

it('respects activities with timestamps disabled', function () {
    DB::beginTransaction();
    $activity = activity()
        ->performedOn(Article::first())
        ->tap(function ($activity) {
            $activity->timestamps = false;
        })
        ->log('without timestamps');
    DB::commit();

    expect(Activity::sole()->id)->toBe($activity->id);
    expect($activity->created_at)->toBeNull();
    expect($activity->updated_at)->toBeNull();
});
