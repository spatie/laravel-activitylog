<?php

namespace Spatie\Activitylog\Test;

use Spatie\Activitylog\Test\Enum\NonBackedEnum;
use Spatie\Activitylog\Test\Models\Activity;
use Spatie\Activitylog\Test\Models\User;

it('can store non backed enum', function () {
    $description = 'ROLE LOG';

    activity()
        ->performedOn(User::first())
        ->withProperty('role', NonBackedEnum::User)->log($description);

    expect(Activity::query()->latest()->first()->description)->toEqual($description);
});
