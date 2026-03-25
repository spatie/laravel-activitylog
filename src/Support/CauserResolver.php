<?php

namespace Spatie\Activitylog\Support;

use Closure;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Exceptions\CouldNotLogActivity;

class CauserResolver
{
    protected AuthManager $authManager;

    protected ?string $authDriver;

    protected ?Closure $resolverOverride = null;

    protected ?Model $causerOverride = null;

    public function __construct(Repository $config, AuthManager $authManager)
    {
        $this->authManager = $authManager;

        $this->authDriver = $config->get('activitylog.default_auth_driver');
    }

    public function resolve(Model|int|string|null $subject = null): ?Model
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

    protected function resolveUsingId(int|string $subject): Model
    {
        $guard = $this->authManager->guard($this->authDriver);

        $provider = $guard->getProvider();
        $model = $provider->retrieveById($subject);

        throw_unless($model instanceof Model, CouldNotLogActivity::couldNotDetermineUser($subject));

        return $model;
    }

    protected function getCauser(Model|int|string|null $subject = null): ?Model
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
     * Override the resolver using callback.
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

    /**
     * Execute a callback with a specific causer, then restore the previous causer.
     */
    public function withCauser(?Model $causer, Closure $callback): mixed
    {
        $previousCauser = $this->causerOverride;
        $this->causerOverride = $causer;

        try {
            return $callback();
        } finally {
            $this->causerOverride = $previousCauser;
        }
    }

    protected function isResolvable(mixed $model): bool
    {
        if ($model instanceof Model) {
            return true;
        }

        return is_null($model);
    }

    protected function getDefaultCauser(): ?Model
    {
        return $this->authManager->guard($this->authDriver)->user();
    }
}
