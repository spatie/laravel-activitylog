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

    $this->assertCount(1, $activityInLog3);

    $this->assertEquals('log3', $activityInLog3->first()->log_name);
});

it('provides a scope to get log items from multiple logs', function () {
    $activity = Activity::inLog('log2', 'log4')->get();

    $this->assertCount(2, $activity);

    $this->assertEquals('log2', $activity->first()->log_name);
    $this->assertEquals('log4', $activity->last()->log_name);
});

it('provides a scope to get log items from multiple logs using an array', function () {
    $activity = Activity::inLog(['log1', 'log2'])->get();

    $this->assertCount(2, $activity);

    $this->assertEquals('log1', $activity->first()->log_name);
    $this->assertEquals('log2', $activity->last()->log_name);
});

it('provides a scope to get log items for a specific causer', function () {
    $subject = Article::first();
    $causer = User::first();

    activity()->on($subject)->by($causer)->log('Foo');
    activity()->on($subject)->by(User::create([
        'name' => 'Another User',
    ]))->log('Bar');

    $activities = Activity::causedBy($causer)->get();

    $this->assertCount(1, $activities);
    $this->assertEquals($causer->getKey(), $activities->first()->causer_id);
    $this->assertEquals(get_class($causer), $activities->first()->causer_type);
    $this->assertEquals('Foo', $activities->first()->description);
});

it('provides a scope to get log items for a specific event', function () {
    $subject = Article::first();
    activity()
        ->on($subject)
        ->event('create')
        ->log('Foo');
    $activities = Activity::forEvent('create')->get();
    $this->assertCount(1, $activities);
    $this->assertEquals('create', $activities->first()->event);
});

it('provides a scope to get log items for a specific subject', function () {
    $subject = Article::first();
    $causer = User::first();

    activity()->on($subject)->by($causer)->log('Foo');
    activity()->on(Article::create([
        'name' => 'Another article',
    ]))->by($causer)->log('Bar');

    $activities = Activity::forSubject($subject)->get();

    $this->assertCount(1, $activities);
    $this->assertEquals($subject->getKey(), $activities->first()->subject_id);
    $this->assertEquals(get_class($subject), $activities->first()->subject_type);
    $this->assertEquals('Foo', $activities->first()->description);
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

    $this->assertCount(1, $activities);
    $this->assertEquals($causer->getKey(), $activities->first()->causer_id);
    $this->assertEquals('users', $activities->first()->causer_type);
    $this->assertEquals('Foo', $activities->first()->description);

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

    $this->assertCount(1, $activities);
    $this->assertEquals($subject->getKey(), $activities->first()->subject_id);
    $this->assertEquals('articles', $activities->first()->subject_type);
    $this->assertEquals('Foo', $activities->first()->description);

    Relation::morphMap([], false);
});
