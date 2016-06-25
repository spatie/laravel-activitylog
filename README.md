# Log activities of the users of your app

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-activitylog.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-activitylog)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/spatie/laravel-activitylog/master.svg?style=flat-square)](https://travis-ci.org/spatie/laravel-activitylog)
[![SensioLabsInsight](https://img.shields.io/sensiolabs/i/20a38dd4-06a0-401f-bd51-1d3f05fcdff5.svg?style=flat-square)](https://insight.sensiolabs.com/projects/20a38dd4-06a0-401f-bd51-1d3f05fcdff5)
[![Quality Score](https://img.shields.io/scrutinizer/g/spatie/laravel-activitylog.svg?style=flat-square)](https://scrutinizer-ci.com/g/spatie/laravel-activitylog)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-activitylog.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-activitylog)

This package provides a function to easily log activities of the users of your app.

Here's an example:

```php
activity()
   ->causedBy($userModel)
   ->performedOn($anEloquentModel)
   ->log('Model was saved');
```

All activity is saved in a `activity_log` table in the database.

Spatie is a webdesign agency based in Antwerp, Belgium. You'll find an overview of all our open source projects [on our website](https://spatie.be/opensource).

## Documentation
You'll find the documentation on [https://docs.spatie.be/laravel-activitylog/v1](https://docs.spatie.be/laravel-activitylog/v1).

Find yourself stuck using the package? Found a bug? Do you have general questions or suggestions for improving the media library? Feel free to [create an issue on GitHub](https://github.com/spatie/laravel-medialibrary/issues), we'll try to address it as soon as possible.

If you've found a bug regarding security please mail [freek@spatie.be](mailto:freek@spatie.be) instead of using the issue tracker.


## Installation

You can install the package via composer:

``` bash
composer require spatie/laravel-activitylog
```

Next, you must install the service provider:

```php
// config/app.php
'providers' => [
    ...
    Spatie\Activitylog\ActivitylogServiceProvider::class,
];
```

You can publish the migration with:
```bash
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="migrations"
```

After the migration has been published you can create the media-table by running the migrations:

```bash
php artisan migrate
```

You can optionally publish the config file with:
```bash
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="config"
```

This is the contents of the published config file:

```php
return [

    /**
     * When running the clean-command all recording activites older than
     *  the number of days specified here will be deleted.
     */
    'delete_records_older_than_days' => 31,
];
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email freek@spatie.be instead of using the issue tracker.

## Credits

- [Freek Van der Herten](https://github.com/freekmurze)
- [Sebastian De Deyne](https://github.com/sebdedeyne)
- [All Contributors](../../contributors)

## About Spatie
Spatie is a webdesign agency based in Antwerp, Belgium. You'll find an overview of all our open source projects [on our website](https://spatie.be/opensource).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
