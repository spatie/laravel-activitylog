<?php

use Illuminate\Database\Eloquent\Relations\Relation;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Test\Models\Article;
use Spatie\Activitylog\Test\Models\User;

uses(TestCase::class);

beforeEach(function () {
    collect(range(1, 5))->each(function (int $index) {
        $logName = "log{$index}";
        activity($logName)->log('hello everybody');
    });
});

it('provides a scope to get activities from a specific log', function () {
    $activityInLog3 = Activity::inLog('log3')->get();

    expect($activityInLog3)->toHaveCount(1);

    expect($activityInLog3->first()->log_name)->toEqual('log3');
});

it('provides a scope to get log items from multiple logs', function () {
    $activity = Activity::inLog('log2', 'log4')->get();

    expect($activity)->toHaveCount(2);

    expect($activity->first()->log_name)->toEqual('log2');
    expect($activity->last()->log_name)->toEqual('log4');
});

it('provides a scope to get log items from multiple logs using an array', function () {
    $activity = Activity::inLog(['log1', 'log2'])->get();

    expect($activity)->toHaveCount(2);

    expect($activity->first()->log_name)->toEqual('log1');
    expect($activity->last()->log_name)->toEqual('log2');
});

it('provides a scope to get log items for a specific causer', function () {
    $subject = Article::first();
    $causer = User::first();

    activity()->on($subject)->by($causer)->log('Foo');
    activity()->on($subject)->by(User::create([
        'name' => 'Another User',
    ]))->log('Bar');

    $activities = Activity::causedBy($causer)->get();

    expect($activities)->toHaveCount(1);
    expect($activities->first()->causer_id)->toEqual($causer->getKey());
    expect($activities->first()->causer_type)->toEqual(get_class($causer));
    expect($activities->first()->description)->toEqual('Foo');
});

it('provides a scope to get log items for a specific event', function () {
    $subject = Article::first();
    activity()
        ->on($subject)
        ->event('create')
        ->log('Foo');
    $activities = Activity::forEvent('create')->get();
    expect($activities)->toHaveCount(1);
    expect($activities->first()->event)->toEqual('create');
});

it('provides a scope to get log items for a specific subject', function () {
    $subject = Article::first();
    $causer = User::first();

    activity()->on($subject)->by($causer)->log('Foo');
    activity()->on(Article::create([
        'name' => 'Another article',
    ]))->by($causer)->log('Bar');

    $activities = Activity::forSubject($subject)->get();

    expect($activities)->toHaveCount(1);
    expect($activities->first()->subject_id)->toEqual($subject->getKey());
    expect($activities->first()->subject_type)->toEqual(get_class($subject));
    expect($activities->first()->description)->toEqual('Foo');
});

it('provides a scope to get log items for a specific morphmapped causer', function () {
    Relation::morphMap([
        'articles' => 'Spatie\Activitylog\Test\Models\Article',
        'users' => 'Spatie\Activitylog\Test\Models\User',
    ]);

    $subject = Article::first();
    $causer = User::first();

    activity()->on($subject)->by($causer)->log('Foo');
    activity()->on($subject)->by(User::create([
        'name' => 'Another User',
    ]))->log('Bar');

    $activities = Activity::causedBy($causer)->get();

    expect($activities)->toHaveCount(1);
    expect($activities->first()->causer_id)->toEqual($causer->getKey());
    expect($activities->first()->causer_type)->toEqual('users');
    expect($activities->first()->description)->toEqual('Foo');

    Relation::morphMap([], false);
});

it('provides a scope to get log items for a specific morphmapped subject', function () {
    Relation::morphMap([
        'articles' => 'Spatie\Activitylog\Test\Models\Article',
        'users' => 'Spatie\Activitylog\Test\Models\User',
    ]);

    $subject = Article::first();
    $causer = User::first();

    activity()->on($subject)->by($causer)->log('Foo');
    activity()->on(Article::create([
        'name' => 'Another article',
    ]))->by($causer)->log('Bar');

    $activities = Activity::forSubject($subject)->get();

    expect($activities)->toHaveCount(1);
    expect($activities->first()->subject_id)->toEqual($subject->getKey());
    expect($activities->first()->subject_type)->toEqual('articles');
    expect($activities->first()->description)->toEqual('Foo');

    Relation::morphMap([], false);
});
