---
title: Requirements
weight: 3
---

This version requires **PHP 8.1+**, **Laravel 10.37.2 or higher** (through Laravel 13), the **MongoDB PHP extension** and the official [`mongodb/laravel-mongodb`](https://github.com/mongodb/laravel-mongodb) package (`^5.8`).

The MongoDB driver conflicts with `illuminate/bus` older than 10.37.2, which sets the effective Laravel 10 floor. Models used with this package should extend `MongoDB\Laravel\Eloquent\Model`, and the published migration uses `MongoDB\Laravel\Schema\Blueprint`.

This fork backports upstream v5's API to PHP 8.1 and Laravel 10; upstream v5 itself requires PHP 8.4 and Laravel 13.
