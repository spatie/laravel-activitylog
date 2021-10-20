---
title: Log Options
weight: 1
---

Customization of how your models will be logged is controlled when implementing `getActivitylogOptions`. You can start from the defaults and override values as needed.

The most basic example of an Activity logged model would be:

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class YourModel extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
```

This call to `LogOptions::defaults()` yields the following default options:

```php
public ?string $logName = null;

public bool $submitEmptyLogs = true;

public bool $logFillable = false;

public bool $logOnlyDirty = false;

public bool $logUnguarded = false;

public array $logAttributes = [];

public array $logExceptAttributes = [];

public array $dontLogIfAttributesChangedOnly = [];

public array $attributeRawValues = [];

public ?Closure $descriptionForEvent = null;
```

## Options methods

### defaults

```php
/**
 * Start configuring model with the default options.
 */
public static function defaults(): LogOption;
```

### logAll

This method is equivalent to `->logOnly(['*'])`.

```php
/**
 * Log all attributes on the model
 */
public function logAll(): LogOption;
```

### logUnguarded

This option will respect the wildcard `*`, `->logAll()` and `->logFillable()` methods.

```php
/**
 * log changes to all the $guarded attributes of the model
 */
public function logUnguarded(): LogOption;
```

### logFillable

This option will respect the wildcard `*`, `->logAll()` and `->logUnguarded()` methods.

```php
/**
 * log changes to all the $fillable attributes of the model
 */
public function logFillable(): LogOption;
```

### dontLogFillable

```php
/**
 * Stop logging $fillable attributes of the model
 */
public function dontLogFillable(): LogOption;
```

### logOnlyDirty

```php
/**
 * Log changes that has actually changed after the update
 */
public function logOnlyDirty(): LogOption;
```

### logOnly

```php
/**
 * Log changes only if these attributes changed
 */
public function logOnly(array $attributes): LogOption;
```

### logExcept

Convenient method for `logOnly()`

```php
/**
 * Exclude these attributes from being logged
 */
public function logExcept(array $attributes): LogOption;
```

### dontLogIfAttributesChangedOnly

```php
/**
 * Don't trigger an activity if these attributes changed logged
 */
public function dontLogIfAttributesChangedOnly(array $attributes): LogOption;
```

### dontSubmitEmptyLogs

```php
/**
 * Dont store empty logs. Storing empty logs can happen when you only
 * want to log a certain attribute but only another changes.
 */
public function dontSubmitEmptyLogs(): LogOption;
```

### submitEmptyLogs

```php
/**
 * Allow storing empty logs. Storing empty logs can happen when you only
 * want to log a certain attribute but only another changes.
 */
public function submitEmptyLogs(): LogOption;
```

### useLogName

```php
/**
 * Customize log name
 */
public function useLogName(string $logName): LogOption;
```

### useAttributeRawValues

```php
/**
 * Skip using mutators for these attributes when logged
 */
public function useAttributeRawValues(array $attributes): LogOption;
```

### setDescriptionForEvent

```php
/**
 * Customize log description using callback
 */
public function setDescriptionForEvent(Closure $callback): LogOption;
```
