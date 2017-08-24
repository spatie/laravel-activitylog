<?php

namespace Spatie\Activitylog\Test\Models;

class User extends BaseUser
{
    protected $table = 'users';

    protected $guarded = [];

    public function articles()
    {
        return $this->hasMany(Article::class);
    }
}
