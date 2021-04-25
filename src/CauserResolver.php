<?php
namespace Spatie\Activitylog;

use Closure;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Spatie\Activitylog\Exceptions\CouldNotLogActivity;

class CauserResolver
{
    protected AuthManager $authManager;

    protected string $authDriver;

    /**
     * User defined model or callback to override default reslover logic
     */
    protected Model | Closure | null $override = null;

    public function __construct()
    {
        $config = app(Repository::class);
        $this->authManager = app(AuthManager::class);

        $this->authDriver = $config['activitylog']['default_auth_driver'] ?? $this->authManager->getDefaultDriver();
    }

    /**
     * Reslove causer based different arguments

     * @param Model|int|Closure|null $subject
     * @return null|Model
     * @throws InvalidArgumentException
     * @throws CouldNotLogActivity
     */
    public function resolve(Model | int | Closure | null $subject = null) : ?Model
    {
        if ($subject instanceof Closure) {
            $this->override = $subject;
        }

        if (! is_null($this->override) || is_null($subject)) {
            return $this->getCauser();
        }

        if ($this->isValidCauser($subject)) {
            return $subject;
        }


        // Resolve the user based on passed id
        $guard = $this->authManager->guard($this->authDriver);

        $provider = method_exists($guard, 'getProvider') ? $guard->getProvider() : null;
        $model = method_exists($provider, 'retrieveById') ? $provider->retrieveById($subject) : null;

        if ($model instanceof Model) {
            return $model;
        }

        throw CouldNotLogActivity::couldNotDetermineUser($subject);
    }

    protected function getCauser(): ?Model
    {
        if (! $this->isValidOverrideType()) {
            return $this->getDefaultCauser();
        }

        $causer = $this->override;

        if ($this->override instanceof Closure) {
            $causer = ($this->override)();
        }

        if (! $this->isValidCauser($causer)) {
            throw CouldNotLogActivity::couldNotDetermineUser($causer);
        }

        return $causer;
    }

    /**
     * Override the resover using callback
     */
    public function resolveUsing(Closure $callback): static
    {
        $this->override = $callback;

        return $this;
    }

    /**
     * Override default causer
     */
    public function setCauser(?Model $causer): static
    {
        $this->override = $causer;

        return $this;
    }

    protected function isValidCauser(mixed $model): bool
    {
        return ($model instanceof Model || is_null($model));
    }

    protected function getDefaultCauser(): ?Model
    {
        return $this->authManager->guard($this->authDriver)->user();
    }

    protected function isValidOverrideType(): bool
    {
        return ($this->override instanceof Model)
        || ($this->override instanceof Closure)
        || null;
    }
}
