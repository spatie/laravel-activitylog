---
title: Causer Resolver
weight: 3
---

This class registered as singleton and will allow you to set causer for activity globally or per action basis.

**Note that** Overriding causer using `setCauser` method takes priority over overriding the resolver using `resolveUsing` method.

```php
    CauserResolver::setCauser(User::find(1));

    $log = activity()->log('log look mom, I did something...');
    $log->causer; // User Model with id of 1
```

## resolve

```php
    /**
     * Reslove causer based different arguments first we'll check for override closure
     * Then check for the result causer if it valid. In other case will return the
     * override causer defined by the user or delgate to the getCauser() method
     *
     * @param Model|int|null $subject
     * @return null|Model
     * @throws InvalidArgumentException
     * @throws CouldNotLogActivity
     */
    public function resolve(Model | int | string | null $subject = null) : ?Model;
```

## resolveUsing

```php
    /**
     * Override the resover using callback
     */
    public function resolveUsing(Closure $callback): static;
```

## setCauser

```php
    /**
     * Override default causer
     */
    public function setCauser(?Model $causer): static;
```
