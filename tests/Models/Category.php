<?php

namespace Spatie\Activitylog\Test\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $table = 'categories';
    protected $primaryKey = 'uuid';
    public $incrementing = false;

    protected $fillable = ['uuid', 'name'];
    protected $visible = ['uuid', 'name'];
    protected $guarded = [];
}
