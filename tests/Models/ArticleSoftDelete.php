<?php

namespace Spatie\Activitylog\Test\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class ArticleSoftDelete extends Article
{
    use SoftDeletes;
}
