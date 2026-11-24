---
title: Requirements
weight: 3
---

This version requires **PHP 8.4+**, **Laravel 13.32 or higher**, the **MongoDB PHP extension** and the official [`mongodb/laravel-mongodb`](https://github.com/mongodb/laravel-mongodb) package (`^5.12`).

Models used with this package should extend `MongoDB\Laravel\Eloquent\Model`, and the published migration uses `MongoDB\Laravel\Schema\Blueprint`.

If you're on an older version of PHP or Laravel, take a look at the [v4](https://spatie.be/docs/laravel-activitylog/v4) documentation of the upstream package.
