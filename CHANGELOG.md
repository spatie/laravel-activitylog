# Changelog

All notable changes to `spatie/laravel-activitylog` will be documented in this file

## 3.2.2 - 2019-02-27

- add support for Laravel 5.8
- fix logging hidden attributes
- fix logging for a causer model without a provider
- add code coverage reporting for repository

## 3.2.1 - 2019-02-01

- use Str:: and Arr:: instead of helper methods

## 3.2.0 - 2019-01-29

- add `ActivityLogger::tap()` method
- add `LogsActivity::tapActivity()` method
- the `ActivityLogger` will work on an activity model instance instead of cache variables

## 3.1.2 - 2018-10-18

- add `shouldLogUnguarded()` method
- fix typo in methodname `shouldLogOnlyDirty()`

## 3.1.1 - 2018-10-17

- fix `$logUnguarded`

## 3.1.0 - 2018-10-17

- add `$logUnguarded`

## 3.0.0 - 2018-10-16 
- the preferred way to get changes on an `Activity` model is through the `changes` property instead of the `changes()` function 
- the `activity` relation of the `CausesActivity` trait has been renamed to `actions`
- the `activity` relation of the `LogsActivity` trait has been renamed to `activities`
- the deprecated `loggedActivity` relation has been removed
- the `HasActivity` trait has been removed.
- fix for setting a custom table name for the `Activity` model via the `$table` property
- support for PHP 7.0 has been dropped

## 2.8.4. - 2018-09-23
- improve migration

## 2.8.3 - 2018-09-01
- add support for L5.7

## 2.8.2 - 2018-07-28
- allow `null` to be passed to `causedBy`

## 2.8.1 - 2018-07-28
- make sure a fresh instance of `ActivityLogger` is used

## 2.8.0 - 2018-07-21
- add `enableLogging()` and `disableLogging()`

## 2.7.0 - 2018-06-18
- add ability to ignore changes to attributes specified in  `$logAttributesToIgnore`

## 2.6.0 - 2018-04-03
- add `table_name` config option

## 2.5.1 - 2018-02-11
- improve support for soft deletes

## 2.5.0 - 2018-02-09
- allow model to override the default log name

## 2.4.2 - 2018-02-08
- add compatibility with L5.6

## 2.4.1 - 2018-01-20
- use a `text` column for `description`

## 2.4.0 - 2018-01-20
- add `HasActivity`

## 2.3.2 - 2017-12-13
- fix bugs concerning `attributesToBeLogged`

## 2.3.1 - 2017-11-13
- allow nullable relation when using `logChanges`

## 2.3.0 - 2017-11-07
- add a `log` argument to `activitylog:clean`

## 2.2.0 - 2017-10-16
- add support for logging all changed attributes using `*`

## 2.1.2 - 2017-09-28
- fix for logging changes attributes when deleting soft deletable models

## 2.1.1 - 2017-09-12
- make sure `properties` always is a collection

## 2.1.0 - 2017-09-19
- added support for logging fillable attributes

## 2.0.0 - 2017-08-30
- added support for Laravel 5.5, dropped support for older laravel versions
- renamed config file from `laravel-activitylog` to `activitylog`
- rename `getChangesAttribute` function to `changes` so it doesn't conflict with Laravel's native functionality

## 1.16.0 - 2017-06-28
- added `enableLogging` and `disableLogging`

## 1.15.5 - 2017-08-08
- fix model scope

## 1.15.4 - 2017-08-05
- fix detecting `SoftDeletes`

## 1.15.3 - 2017-06-23
- fix for when there is no 'web' guard

## 1.15.2 - 2017-06-15
- fixes errors in `DetectsChanges`

## 1.15.1 - 2017-04-28
- fixes error in `DetectsChanges`

## 1.15.0 - 2017-04-28
- add compatibility with L5.1 and L5.2

## 1.14.0 - 2017-04-16
- add support array/collection casted attributes when using `logDirtyOnly`

## 1.13.0 - 2017-04-16
- add `logDirtyOnly`

## 1.12.2 - 2017-03-22
- fix a bug where changes to a related model would not be logged

## 1.12.1 - 2017-02-12
- avoid PHP error when dealing with placeholders that cannot be filled

## 1.12.0 - 2017-02-04
- drop support for L5.2 and lower
- add ability to log attributes of related models

## 1.11.0 - 2017-01-23
- add support for L5.4

## 1.10.4 - 2017-01-20
- `Activity` now extends from `Model` instead of `Eloquent`

## 1.10.2 - 2016-11-26
- fix compatibilty for Laravel 5.1

## 1.10.1 - 2016-10-11
- fix `scopeCausedBy` and `scopeForSubject`

## 1.10.0 - 2016-10-10
- add support for `restored` event

## 1.9.2 - 2016-09-27 
- fixed a bug where the delete event would not be logged

## 1.9.1 - 2016-09-16
- fixed the return value of `activity()->log()`. It will now return the created `Activity`-model.

## 1.9.0 - 2016-09-16
- added `Macroable` to `ActivityLogger`

## 1.8.0 - 2016-09-12
- added `causedBy` and `forSubject` scopes

## 1.7.1 - 2016-08-23
- Added L5.3 compatibility

## 1.7.0 - 2016-08-17
- Added `enabled` option in the config file.

## 1.6.0 - 2016-08-11
- Added `ignoreChangedAttributes`

## 1.5.0 - 2016-08-11
- Added support for using a custom `Activity` model

## 1.4.0 - 2016-08-10
- Added support for soft deletes

## 1.3.2 - 2016-08-09
- This version replaces version `1.3.0`
- Dropped L5.1 compatibility

## 1.3.1 - 2016-08-09
- this version removes the features introduced in 1.3.0 and is compatible with L5.1

## 1.3.0 - 2016-07-29

**DO NOT USE THIS VERSION IF YOU'RE ON L5.1**

Please upgrade to:
- `1.3.1` for Laravel 5.1
- `1.3.2` for Laravel 5.2 and higher

Introduced features
- made the auth driver configurable

## 1.3.0 - 2016-07-29

- made the auth driver configurable

## 1.2.1 - 2016-07-09

- use config repo contract

## 1.2.0 - 2016-07-08

- added `getLogNameToUse`

## 1.1.0 - 2016-07-04

- added `activity`-method on both the `CausesActivity` and `LogsActivity`-trait

## 1.0.3 - 2016-07-01

- the package is now compatible with Laravel 5.1

## 1.0.2 - 2016-06-29

- fixed naming of `inLog` scope
- add `inLog` function alias

## 1.0.1 - 2016-06-29

- fixed error when publishing migrations

## 1.0.0 - 2016-06-28

- initial release
