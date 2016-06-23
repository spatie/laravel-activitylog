<?php

namespace Spatie\Activitylog\Test\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\CausesActivity;

class User extends Model
{
    use CausesActivity;

    protected $guarded = [];
}