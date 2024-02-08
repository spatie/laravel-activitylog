<?php

namespace Spatie\Activitylog\Test\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\CausesActivity;

class User extends Model implements Authenticatable
{
    use CausesActivity;

    protected $table = 'users';

    protected $guarded = [];

    protected $fillable = ['id', 'name'];

    public function getAuthIdentifierName()
    {
        return 'id';
    }

    public function getAuthIdentifier()
    {
        $name = $this->getAuthIdentifierName();

        return $this->attributes[$name];
    }

    public function getAuthPasswordName()
    {
        return 'password';
    }

    public function getAuthPassword()
    {
        return $this->attributes['password'];
    }

    public function getRememberToken()
    {
        return 'token';
    }

    public function setRememberToken($value)
    {
    }

    public function getRememberTokenName()
    {
        return 'tokenName';
    }

    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    public function latestArticle()
    {
        return $this->hasOne(Article::class)->latest()->limit(1);
    }
}
