<?php

use Carbon\Carbon;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Traits\AllowsMassPruning;

beforeEach(function () {
    Carbon::setTestNow(Carbon::create(2016, 1, 1, 00, 00, 00));

    app()['config']->set('activitylog.delete_records_older_than_days', 31);
});

it('can clean the activity log', function (Activity $activityImpl) {
    app()['config']->set('activitylog.activity_model', get_class($activityImpl));

    collect(range(1, 60))->each(function (int $index) use ($activityImpl) {
        $activityImpl::create([
            'description' => "item {$index}",
            'created_at' => Carbon::now()->subDays($index)->startOfDay(),
        ]);
    });

    expect($activityImpl::all())->toHaveCount(60);

    Artisan::call('activitylog:clean');

    expect($activityImpl::all())->toHaveCount(31);

    $cutOffDate = Carbon::now()->subDays(31)->format('Y-m-d H:i:s');

    expect($activityImpl::where('created_at', '<', $cutOffDate)->get())->toHaveCount(0);
})->with([
    'mass pruning' => fn() => new Spatie\Activitylog\Models\Activity,
    'regular deletion' => fn() => new Spatie\Activitylog\Test\Models\Activity,
]);

it('can accept days as option to override config setting', function (Activity $activityImpl) {
    app()['config']->set('activitylog.activity_model', get_class($activityImpl));

    collect(range(1, 60))->each(function (int $index) use ($activityImpl) {
        $activityImpl::create([
            'description' => "item {$index}",
            'created_at' => Carbon::now()->subDays($index)->startOfDay(),
        ]);
    });

    expect($activityImpl::all())->toHaveCount(60);

    Artisan::call('activitylog:clean', ['--days' => 7]);

    expect($activityImpl::all())->toHaveCount(7);

    $cutOffDate = Carbon::now()->subDays(7)->format('Y-m-d H:i:s');

    expect($activityImpl::where('created_at', '<', $cutOffDate)->get())->toHaveCount(0);
})->with([
    'mass pruning' => fn() => new Spatie\Activitylog\Models\Activity,
    'regular deletion' => fn() => new Spatie\Activitylog\Test\Models\Activity,
]);
