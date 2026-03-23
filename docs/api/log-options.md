---
title: Log Options
weight: 1
---

Customization of how your models will be logged is controlled by implementing `getActivitylogOptions()`. This method is optional. If not implemented, the package uses sensible defaults (logs events but no attribute changes).

The most basic example of an Activity logged model is:

```php
use Illuminate\Database\Eloquent\Model;use Spatie\Activitylog\Models\Concerns\LogsActivity;

class YourModel extends Model
{
    use LogsActivity;
}
```

To customize what gets logged, override `getActivitylogOptions()`:

```php
use Illuminate\Database\Eloquent\Model;use Spatie\Activitylog\Models\Concerns\LogsActivity;use Spatie\Activitylog\Support\LogOptions;

class YourModel extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logFillable()
        ->logOnlyDirty();
    }
}
```

The call to `LogOptions::defaults()` yields the following default options:

```php
public ?string $logName = null;

public bool $logEmptyChanges = true;

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
public static function defaults(): LogOptions;
```

### logAll

This method is equivalent to `->logOnly(['*'])`.

```php
/**
 * Log all attributes on the model.
 */
public function logAll(): LogOptions;
```

### logUnguarded

This option will respect the wildcard `*`, `->logAll()` and `->logFillable()` methods.

```php
/**
 * Log changes to all the $guarded attributes of the model.
 */
public function logUnguarded(): LogOptions;
```

### logFillable

This option will respect the wildcard `*`, `->logAll()` and `->logUnguarded()` methods.

```php
/**
 * Log changes to all the $fillable attributes of the model.
 */
public function logFillable(): LogOptions;
```

### dontLogFillable

```php
/**
 * Stop logging $fillable attributes of the model.
 */
public function dontLogFillable(): LogOptions;
```

### logOnlyDirty

```php
/**
 * Log changes that have actually changed after the update.
 */
public function logOnlyDirty(): LogOptions;
```

### logOnly

```php
/**
 * Log changes only if these attributes changed.
 */
public function logOnly(array $attributes): LogOptions;
```

### logExcept

Convenient method for excluding specific attributes from logging.

```php
/**
 * Exclude these attributes from being logged.
 */
public function logExcept(array $attributes): LogOptions;
```

### dontLogIfAttributesChangedOnly

```php
/**
 * Don't trigger an activity if only these attributes changed.
 */
public function dontLogIfAttributesChangedOnly(array $attributes): LogOptions;
```

### dontLogEmptyChanges

```php
/**
 * Don't store empty logs. Storing empty logs can happen when you only
 * want to log a certain attribute but only another changes.
 */
public function dontLogEmptyChanges(): LogOptions;
```

### logEmptyChanges

```php
/**
 * Allow storing empty logs.
 */
public function logEmptyChanges(): LogOptions;
```

### useLogName

```php
/**
 * Customize log name.
 */
public function useLogName(string $logName): LogOptions;
```

### useAttributeRawValues

```php
/**
 * Skip using mutators for these attributes when logged.
 */
public function useAttributeRawValues(array $attributes): LogOptions;
```

### setDescriptionForEvent

```php
/**
 * Customize log description using callback.
 */
public function setDescriptionForEvent(Closure $callback): LogOptions;
```
