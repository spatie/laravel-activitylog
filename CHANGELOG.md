# Changelog

All notable changes to `spatie/laravel-activitylog` will be documented in this file

## 3.16.0 - 2020-09-16

- use `nullableMorphs()` in default migration - [#707](https://github.com/spatie/laravel-activitylog/pull/707)
- add support for snake and camel cased related model attribute logging - [#721](https://github.com/spatie/laravel-activitylog/pull/721)

## 3.15.0 - 2020-09-14

- Add multiple/chained relation attribute logging support - [#784](https://github.com/spatie/laravel-activitylog/pull/784)

## 3.14.3 - 2020-09-09

- Add support for Laravel 8

## 3.14.2 - 2020-05-19

- fix `retrieved` event logging

## 3.14.1 - 2020-03-23

- revert breaking changes in `v3.14.0`

## 3.14.0 - 2020-03-23 - BC

Please use `v3.14.1` instead - this release is breaking because of the new column. There is also a `v4.0.0-rc.1` release that equals to this one.

- add `\Spatie\Activitylog\ActivityLogger::event()` method and column [#702](https://github.com/spatie/laravel-activitylog/pull/702)

## 3.13.0 - 2020-03-13

- add `\Spatie\Activitylog\ActivityLogger::withoutLogs()` method [#695](https://github.com/spatie/laravel-activitylog/pull/695)

## 3.12.0 - 2020-03-13

- respect custom date casts [#627](https://github.com/spatie/laravel-activitylog/pull/627)

## 3.11.4 - 2020-03-11

- remove `spatie/string` dependency [#690](https://github.com/spatie/laravel-activitylog/pull/690)

## 3.11.3 - 2020-03-10

- fix performance issue around global vs model log disabling [#682](https://github.com/spatie/laravel-activitylog/pull/682)

## 3.11.2 - 2020-03-10

- fix Laravel 7 array/json casted attributes [#680](https://github.com/spatie/laravel-activitylog/pull/680)

## 3.11.1 - 2020-03-02

- fix requirements

## 3.11.0 - 2020-03-02

- add support for Laravel 7

## 3.10.0 - 2020-02-22

- add ability to manually set created at date - [#622](https://github.com/spatie/laravel-activitylog/pull/622)

## 3.9.2 - 2020-02-04

- drop support for Laravel 5

## 3.9.1 - 2019-10-15

- fix default database connection - [#616](https://github.com/spatie/laravel-activitylog/pull/616)

## 3.9.0 - 2019-10-06

- add anonymous causer with `null` value - [#605](https://github.com/spatie/laravel-activitylog/pull/605)
- fix relationships to allow snake case keys - [#602](https://github.com/spatie/laravel-activitylog/pull/602)
- add JOSN sub-key attribute logging - [#601](https://github.com/spatie/laravel-activitylog/pull/601)

## 3.8.0 - 2019-09-04

- add support for Laravel 6
- change fields with value `null` to be strictly compared when logging dirty fields [#453](https://github.com/spatie/laravel-activitylog/pull/453)
- add composite indexes for subject and causer to migration

## 3.7.2 - 2019-08-28

- do not export docs folder

## 3.7.1 - 2019-07-24

- fix default database connection env var

## 3.7.0 - 2019-07-23

- add database connection to configuration `activitylog.database_connection` and `ACTIVITY_LOGGER_DB_CONNECTION` env var [#568](https://github.com/spatie/laravel-activitylog/pull/568)

## 3.6.3 - 2019-07-23

- fix deprecated `array_` helper [#569](https://github.com/spatie/laravel-activitylog/pull/569)

## 3.6.2 - 2019-07-16

- fix existing description [#563](https://github.com/spatie/laravel-activitylog/pull/563)

## 3.6.1 - 2019-05-29

- fix nullable date attributes [#546](https://github.com/spatie/laravel-activitylog/pull/546)

## 3.6.0 - 2019-05-28

- update `properties` column type from `text` to `json` [#525](https://github.com/spatie/laravel-activitylog/pull/525)
- update `subject_id` and `causer_id` column type from `integer` to `big_integer` and `unsigned` [#527](https://github.com/spatie/laravel-activitylog/pull/527)
- fix attribute getter support in `DetectsChanges` trait [#534](https://github.com/spatie/laravel-activitylog/pull/534)
- fix old attributes retrieval in `DetectsChanges` trait [#537](https://github.com/spatie/laravel-activitylog/pull/537)
- clean up old attributes in `DetectsChanges` trait [#538](https://github.com/spatie/laravel-activitylog/pull/538)

## 3.5.0 - 2019-04-15

- add days option to clean command [#497](https://github.com/spatie/laravel-activitylog/pull/497)
- add `LogsActivity::$submitEmptyLogs` [#514](https://github.com/spatie/laravel-activitylog/pull/514)

## 3.4.0 - 2019-04-09

- use `Illuminate\Contracts\Config\Repository` instead of `Illuminate\Config\Repository` [#505](https://github.com/spatie/laravel-activitylog/pull/505)
- fix `logChanges()` [#512](https://github.com/spatie/laravel-activitylog/pull/512)

## 3.3.0 - 2019-04-08

- drop support for Laravel 5.7 and lower
- drop support for PHP 7.1 and lower

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
