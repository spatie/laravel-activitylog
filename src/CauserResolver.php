<?php

namespace Spatie\Activitylog;

use Closure;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Exceptions\CouldNotLogActivity;

class CauserResolver
{
    protected AuthManager $authManager;

    protected string | null $authDriver;

    protected Closure | null $resolverOverride = null;

    protected Model | null $causerOverride = null;

    public function __construct(Repository $config, AuthManager $authManager)
    {
        $this->authManager = $authManager;

        $this->authDriver = $config['activitylog']['default_auth_driver'];
    }

    public function resolve(Model | int | string | null $subject = null): ?Model
    {
        if ($this->causerOverride !== null) {
            return $this->causerOverride;
        }

        if ($this->resolverOverride !== null) {
            $resultCauser = ($this->resolverOverride)($subject);

            if (! $this->isResolvable($resultCauser)) {
                throw CouldNotLogActivity::couldNotDetermineUser($resultCauser);
            }

            return $resultCauser;
        }

        return $this->getCauser($subject);
    }

    protected function resolveUsingId(int | string $subject): Model
    {
        $guard = $this->authManager->guard($this->authDriver);

        $provider = method_exists($guard, 'getProvider') ? $guard->getProvider() : null;
        $model = method_exists($provider, 'retrieveById') ? $provider->retrieveById($subject) : null;

        throw_unless($model instanceof Model, CouldNotLogActivity::couldNotDetermineUser($subject));

        return $model;
    }

    protected function getCauser(Model | int | string | null $subject = null): ?Model
    {
        if ($subject instanceof Model) {
            return $subject;
        }

        if (is_null($subject)) {
            return $this->getDefaultCauser();
        }

        return $this->resolveUsingId($subject);
    }

    /**
     * Override the resover using callback.
     */
    public function resolveUsing(Closure $callback): static
    {
        $this->resolverOverride = $callback;

        return $this;
    }

    /**
     * Override default causer.
     */
    public function setCauser(?Model $causer): static
    {
        $this->causerOverride = $causer;

        return $this;
    }

    protected function isResolvable(mixed $model): bool
    {
        return $model instanceof Model || is_null($model);
    }

    protected function getDefaultCauser(): ?Model
    {
        return $this->authManager->guard($this->authDriver)->user();
    }
}
