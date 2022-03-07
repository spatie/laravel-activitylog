<?php

use Spatie\Activitylog\Test\Models\User;

uses(TestCase::class);

it('can get all activity for the causer', function () {
    $causer = User::first();

    activity()->by($causer)->log('perform activity');
    activity()->by($causer)->log('perform another activity');

    $this->assertCount(2, $causer->actions);
});
