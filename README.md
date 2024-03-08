<p align="center"><img src="/art/socialcard.png" alt="Social Card of Laravel Activity Log"></p>

# Log activity inside your Laravel app

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-activitylog.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-activitylog)
[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/spatie/laravel-activitylog/run-tests.yml?branch=main&label=Tests)](https://github.com/spatie/laravel-activitylog/actions/workflows/run-tests.yml)
[![Check & fix styling](https://github.com/spatie/laravel-activitylog/workflows/Check%20&%20fix%20styling/badge.svg)](https://github.com/spatie/laravel-activitylog/actions/workflows/php-cs-fixer.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-activitylog.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-activitylog)

The `spatie/laravel-activitylog` package provides easy to use functions to log the activities of the users of your app. It can also automatically log model events.
The Package stores all activity in the `activity_log` table.

Here's a demo of how you can use it:

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

Here's an example on [event logging](https://spatie.be/docs/laravel-activitylog/advanced-usage/logging-model-events).

```php
$newsItem->name = 'updated name';
$newsItem->save();

//updating the newsItem will cause the logging of an activity
$activity = Activity::all()->last();

$activity->description; //returns 'updated'
$activity->subject; //returns the instance of NewsItem that was saved
```

Calling `$activity->changes()` will return this array:

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

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/laravel-activitylog.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/laravel-activitylog)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Documentation

You'll find the documentation on [https://spatie.be/docs/laravel-activitylog/introduction](https://spatie.be/docs/laravel-activitylog/introduction).

Find yourself stuck using the package? Found a bug? Do you have general questions or suggestions for improving the activity log? Feel free to [create an issue on GitHub](https://github.com/spatie/laravel-activitylog/issues), we'll try to address it as soon as possible.

## Installation

You can install the package via composer:

```bash
composer require spatie/laravel-activitylog
```

The package will automatically register itself.

You can publish the migration with:

```bash
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
```

_Note_: The default migration assumes you are using integers for your model IDs. If you are using UUIDs, or some other format, adjust the format of the subject_id and causer_id fields in the published migration before continuing.

After publishing the migration you can create the `activity_log` table by running the migrations:

```bash
php artisan migrate
```

You can optionally publish the config file with:

```bash
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-config"
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information about recent changes.

## Upgrading

Please see [UPGRADING](UPGRADING.md) for details.

## Testing

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security

If you've found a bug regarding security please mail [security@spatie.be](mailto:security@spatie.be) instead of using the issue tracker.

## Credits

-   [Freek Van der Herten](https://github.com/freekmurze)
-   [Sebastian De Deyne](https://github.com/sebastiandedeyne)
-   [Tom Witkowski](https://github.com/Gummibeer)
-   [All Contributors](../../contributors)

And a special thanks to [Caneco](https://twitter.com/caneco) for the logo and [Ahmed Nagi](https://github.com/nagi1) for all the work he put in `v4`.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
