<?php

use Artisan;
use Carbon\Carbon;
use Spatie\Activitylog\Models\Activity;

uses(TestCase::class);

beforeEach(function () {
    Carbon::setTestNow(Carbon::create(2016, 1, 1, 00, 00, 00));

    app()['config']->set('activitylog.delete_records_older_than_days', 31);
});

it('can clean the activity log', function () {
    collect(range(1, 60))->each(function (int $index) {
        Activity::create([
            'description' => "item {$index}",
            'created_at' => Carbon::now()->subDays($index)->startOfDay(),
        ]);
    });

    $this->assertCount(60, Activity::all());

    Artisan::call('activitylog:clean');

    $this->assertCount(31, Activity::all());

    $cutOffDate = Carbon::now()->subDays(31)->format('Y-m-d H:i:s');

    $this->assertCount(0, Activity::where('created_at', '<', $cutOffDate)->get());
});

it('can accept days as option to override config setting', function () {
    collect(range(1, 60))->each(function (int $index) {
        Activity::create([
            'description' => "item {$index}",
            'created_at' => Carbon::now()->subDays($index)->startOfDay(),
        ]);
    });

    $this->assertCount(60, Activity::all());

    Artisan::call('activitylog:clean', ['--days' => 7]);

    $this->assertCount(7, Activity::all());

    $cutOffDate = Carbon::now()->subDays(7)->format('Y-m-d H:i:s');

    $this->assertCount(0, Activity::where('created_at', '<', $cutOffDate)->get());
});
