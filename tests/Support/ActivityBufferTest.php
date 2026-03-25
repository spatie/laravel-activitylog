<?php

use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Support\ActivityBuffer;
use Spatie\Activitylog\Test\Models\Article;
use Spatie\Activitylog\Test\Models\User;

beforeEach(function () {
    config(['activitylog.buffer.enabled' => true]);
});

it('buffers activities instead of saving them immediately', function () {
    activity()->log('buffered activity');

    expect(Activity::count())->toBe(0);
    expect(app(ActivityBuffer::class)->hasPending())->toBeTrue();
});

it('flushes buffered activities to the database', function () {
    activity()->log('first activity');
    activity()->log('second activity');

    expect(Activity::count())->toBe(0);

    app(ActivityBuffer::class)->flush();

    expect(Activity::count())->toBe(2);
    expect(Activity::first()->description)->toBe('first activity');
    expect(Activity::orderBy('id', 'desc')->first()->description)->toBe('second activity');
});

it('saves activities immediately when buffer is disabled', function () {
    config(['activitylog.buffer.enabled' => false]);

    activity()->log('immediate activity');

    expect(Activity::count())->toBe(1);
    expect(app(ActivityBuffer::class)->hasPending())->toBeFalse();
});

it('flushes with correct properties', function () {
    $properties = ['key' => 'value', 'nested' => ['sub' => 'data']];

    activity()
        ->withProperties($properties)
        ->log('activity with properties');

    app(ActivityBuffer::class)->flush();

    $activity = Activity::first();

    expect($activity->description)->toBe('activity with properties');
    expect($activity->getProperty('key'))->toBe('value');
    expect($activity->getProperty('nested.sub'))->toBe('data');
});

it('flushes with correct subject and causer', function () {
    $article = Article::first();
    $user = User::first();

    activity()
        ->performedOn($article)
        ->causedBy($user)
        ->log('activity with relations');

    app(ActivityBuffer::class)->flush();

    $activity = Activity::first();

    expect($activity->subject_type)->toBe($article->getMorphClass());
    expect($activity->subject_id)->toBe($article->getKey());
    expect($activity->causer_type)->toBe($user->getMorphClass());
    expect($activity->causer_id)->toBe($user->getKey());
});

it('flushes with correct log name', function () {
    activity('custom-log')->log('named log activity');

    app(ActivityBuffer::class)->flush();

    expect(Activity::first()->log_name)->toBe('custom-log');
});

it('flushes with correct event', function () {
    activity()
        ->event('created')
        ->log('event activity');

    app(ActivityBuffer::class)->flush();

    expect(Activity::first()->event)->toBe('created');
});

it('flushes with correct attribute changes', function () {
    activity()
        ->withChanges(['attributes' => ['name' => 'new'], 'old' => ['name' => 'old']])
        ->log('changes activity');

    app(ActivityBuffer::class)->flush();

    $activity = Activity::first();

    expect($activity->attribute_changes->toArray())->toBe([
        'attributes' => ['name' => 'new'],
        'old' => ['name' => 'old'],
    ]);
});

it('preserves a custom created_at timestamp', function () {
    $customDate = now()->subDays(5);

    activity()
        ->createdAt($customDate)
        ->log('backdated activity');

    app(ActivityBuffer::class)->flush();

    expect(Activity::first()->created_at->toAtomString())->toBe($customDate->toAtomString());
});

it('sets timestamps automatically when not provided', function () {
    activity()->log('auto timestamp');

    app(ActivityBuffer::class)->flush();

    $activity = Activity::first();

    expect($activity->created_at)->not->toBeNull();
    expect($activity->updated_at)->not->toBeNull();
});

it('is a no-op when flushing an empty buffer', function () {
    app(ActivityBuffer::class)->flush();

    expect(Activity::count())->toBe(0);
});

it('clears the buffer after flushing', function () {
    activity()->log('will be flushed');

    $buffer = app(ActivityBuffer::class);

    expect($buffer->hasPending())->toBeTrue();
    expect($buffer->count())->toBe(1);

    $buffer->flush();

    expect($buffer->hasPending())->toBeFalse();
    expect($buffer->count())->toBe(0);
});

it('can flush multiple times safely', function () {
    activity()->log('first');

    $buffer = app(ActivityBuffer::class);

    $buffer->flush();
    $buffer->flush();

    expect(Activity::count())->toBe(1);
});

it('resolves description placeholders before buffering', function () {
    $article = Article::create(['name' => 'my article']);

    activity()
        ->performedOn($article)
        ->log('Subject is :subject.name');

    app(ActivityBuffer::class)->flush();

    expect(Activity::first()->description)->toBe('Subject is my article');
});
