# Log activity inside your Laravel app

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-activitylog.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-activitylog)
[![Build Status](https://img.shields.io/travis/spatie/laravel-activitylog/master.svg?style=flat-square)](https://travis-ci.org/spatie/laravel-activitylog)
[![SensioLabsInsight](https://img.shields.io/sensiolabs/i/20a38dd4-06a0-401f-bd51-1d3f05fcdff5.svg?style=flat-square)](https://insight.sensiolabs.com/projects/20a38dd4-06a0-401f-bd51-1d3f05fcdff5)
[![Quality Score](https://img.shields.io/scrutinizer/g/spatie/laravel-activitylog.svg?style=flat-square)](https://scrutinizer-ci.com/g/spatie/laravel-activitylog)
[![StyleCI](https://styleci.io/repos/61802818/shield)](https://styleci.io/repos/61802818)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-activitylog.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-activitylog)

The `spatie/laravel-activitylog` package provides easy to use functions to log the activities of the users of your app. It can also automatically log model events. All activity will be stored in the `activity_log` table.

Here's a litte demo of how you can use it:

```php
activity()->log('Look, I logged something');
```

You can retrieve all activity using the `Spatie\Activitylog\Models\Activity` model.

```php
Activity::all();
```

Here's a more advanced example:
```php
activity()
   ->performedOn($anEloquentModel)
   ->causedBy($user)
   ->withProperties(['customProperty' => 'customValue'])
   ->log('Look, I logged something');
   
$lastLoggedActivity = Activity::all()->last();

$lastLoggedActivity->subject; //returns an instance of an eloquent model
$lastLoggedActivity->causer; //returns an instance of your user model
$lastLoggedActivity->getExtraProperty('customProperty'); //returns 'customValue'
$lastLoggedActivity->description; //returns 'Look, I logged something'
```


Here's an example on [event logging](https://docs.spatie.be/laravel-activitylog/v1/advanced-usage/logging-model-events).

```php
$newsItem->name = 'updated name';
$newsItem->save();

//updating the newsItem will cause an activity being logged
$activity = Activity::all()->last();

$activity->description; //returns 'updated'
$activity->subject; //returns the instance of NewsItem that was created
```

Calling `$activity->changes` will return this array:

```php
[
   'attributes' => [
        'name' => 'updated name',
        'text' => 'Lorum',
    ],
    'old' => [
        'name' => 'original name',
        'text' => 'Lorum',
    ],
];
```

Spatie is a webdesign agency based in Antwerp, Belgium. You'll find an overview of all our open source projects [on our website](https://spatie.be/opensource).

## Postcardware

You're free to use this package (it's [MIT-licensed](LICENSE.md)), but if it makes it to your production environment you are required to send us a postcard from your hometown, mentioning which of our package(s) you are using.

Our address is: Spatie, Samberstraat 69D, 2060 Antwerp, Belgium.

The best postcards will get published on the open source page on our website.

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

*Note*: The default migration assumes you are using integers for your model IDs. If you are using UUIDs, or some other format, adjust the format of the subject_id and causer_id fields in the published migration before continuing.

After the migration has been published you can create the `activity_log` table by running the migrations:


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
     * When set to false, no activities will be saved to database.
     */
    'enabled' => env('ACTIVITY_LOGGER_ENABLED', true),

    /**
     * When running the clean-command all recording activites older than
     * the number of days specified here will be deleted.
     */
    'delete_records_older_than_days' => 365,


    /**
     * When not specifying a log name when logging activity
     * we'll using this log name.
     */
    'default_log_name' => 'default',


    /**
     * When set to true, the subject returns soft deleted models.
     */
     'subject_returns_soft_deleted_models' => false,
     
     
    /**
     * This model will be used to log activity. The only requirement is that
     * it should be or extend the Spatie\Activitylog\Models\Activity model.
     */
    'activity_model' => \Spatie\Activitylog\Models\Activity::class,     
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
- [Sebastian De Deyne](https://github.com/sebastiandedeyne)
- [All Contributors](../../contributors)

## About Spatie
Spatie is a webdesign agency based in Antwerp, Belgium. You'll find an overview of all our open source projects [on our website](https://spatie.be/opensource).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
