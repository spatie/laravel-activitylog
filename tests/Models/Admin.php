<?php

namespace Spatie\Activitylog\Test\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\CausesActivity;

class Admin extends User implements Authenticatable
{
    protected $table = 'admins';
}
