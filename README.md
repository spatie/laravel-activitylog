<div align="left">
    <a href="https://spatie.be/open-source?utm_source=github&utm_medium=banner&utm_campaign=laravel-activitylog">
      <picture>
        <source media="(prefers-color-scheme: dark)" srcset="https://spatie.be/packages/header/laravel-activitylog/html/dark.webp">
        <img alt="Logo for laravel-activitylog" src="https://spatie.be/packages/header/laravel-activitylog/html/light.webp">
      </picture>
    </a>

<h1>Log activity inside your Laravel app</h1>

[![Latest Version on Packagist](https://img.shields.io/packagist/v/vsrklabs/laravel-mongo-activitylog.svg?style=flat-square)](https://packagist.org/packages/vsrklabs/laravel-mongo-activitylog)
[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/vsrklabs/laravel-mongo-activitylog/run-tests.yml?branch=main&label=Tests)](https://github.com/vsrklabs/laravel-mongo-activitylog/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/vsrklabs/laravel-mongo-activitylog.svg?style=flat-square)](https://packagist.org/packages/vsrklabs/laravel-mongo-activitylog)
    
</div>

The `vsrklabs/laravel-mongo-activitylog` package provides easy to use functions to log the activities of the users of your app. It can also automatically log model events.
The package stores all activity in the `activity_log` MongoDB collection.

It is based on `spatie/laravel-activitylog` v5 and stores activity in MongoDB through the official [Laravel MongoDB](https://github.com/mongodb/laravel-mongodb) package (`mongodb/laravel-mongodb`). Models used with this package should extend `MongoDB\Laravel\Eloquent\Model`.

Supported versions: PHP 8.1+, Laravel 10.37.2 through 13, and `mongodb/laravel-mongodb ^5.8`. The v5 API is backported so Laravel 10 remains supported even though upstream v5 requires PHP 8.4 and Laravel 13.

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
$lastLoggedActivity->getProperty('customProperty'); //returns 'customValue'
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

Calling `$activity->attribute_changes` will return this collection:

```php
[
   'attributes' => [
        'name' => 'updated name',
        'text' => 'Lorem',
    ],
    'old' => [
        'name' => 'original name',
        'text' => 'Lorem',
    ],
];
```

## Support us

[<img src="https://spatie.be/github-ad-image/laravel-activitylog.jpg" width="419px" />](https://spatie.be/github-ad-click/laravel-activitylog)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Documentation

You'll find the upstream documentation on [https://spatie.be/docs/laravel-activitylog/introduction](https://spatie.be/docs/laravel-activitylog/introduction). MongoDB-specific notes are kept in this repository's `docs/` folder.

Find yourself stuck using the package? Found a bug? Do you have general questions or suggestions for improving the activity log? Feel free to [create an issue on GitHub](https://github.com/vsrklabs/laravel-mongo-activitylog/issues), we'll try to address it as soon as possible.

## Installation

You can install the package via composer:

```bash
composer require vsrklabs/laravel-mongo-activitylog
```

The package will automatically register itself.

Configure a `mongodb` connection in your application's `config/database.php` and make it the default connection (or use a custom Activity model with its own `$connection`). The published migration uses `MongoDB\Laravel\Schema\Blueprint` to create the `activity_log` collection.

You can publish the migration with:

```bash
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
```

_Note_: The default migration assumes you are using integers for your model IDs. If you are using UUIDs, or some other format, adjust the format of the `subject_id` and `causer_id` fields in the published migration before continuing.

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

The test suite needs a MongoDB server, preferably started as a replica set because the transaction tests use multi-document transactions.

```bash
MONGODB_URI=mongodb://127.0.0.1:27017 MONGODB_DATABASE=activitylog_test composer test
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

Special thanks to [Ahmed Nagi](https://github.com/nagi1) for all the work he put in `v4` and to [Caneco](https://twitter.com/caneco) for the original logo.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
