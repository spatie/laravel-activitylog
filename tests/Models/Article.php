<?php

namespace Spatie\Activitylog\Test\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    protected $table = 'articles';

    protected $guarded = [];
}
