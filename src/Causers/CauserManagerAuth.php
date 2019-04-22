<?php

namespace Spatie\Activitylog\Causers;

use Spatie\Activitylog\Contracts\CauserManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Config\Repository;

class CauserManagerAuth implements CauserManager
{
    /** @var \Illuminate\Auth\AuthManager */
    protected $auth;

    /** @var string */
    protected $authDriver;


    public function __construct(AuthManager $auth, Repository $config)
    {
        $this->auth = $auth;

        $this->authDriver = $config['activitylog']['default_auth_driver'] ?? $auth->getDefaultDriver();

    }

    public function getCauser($modelOrId)
    {
        $guard = $this->auth->guard($this->authDriver);
        $provider = method_exists($guard, 'getProvider') ? $guard->getProvider() : null;
        $model = method_exists($provider, 'retrieveById') ? $provider->retrieveById($modelOrId) : null;

        return $model;
    }

    public function getDefaultCauser() {
        return $this->auth->guard($this->authDriver)->user();
    }

}
