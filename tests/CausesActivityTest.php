<?php

use Spatie\Activitylog\Test\Models\User;

uses(TestCase::class);

it('can get all activity for the causer', function () {
    $causer = User::first();

    activity()->by($causer)->log('perform activity');
    activity()->by($causer)->log('perform another activity');

    expect($causer->actions)->toHaveCount(2);
});
