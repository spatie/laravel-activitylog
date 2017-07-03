<?php

namespace Spatie\Activitylog\Test\Models;

class OtherGuardUser extends BaseUser
{
    protected $table = 'other_guard_users';

    protected $guard = 'other_guard_user';

    protected $guarded = [];
}
