---
title: Faking logging
weight: 7
---

When writing test code, you may wish to 'mock' activity logging. This will allow you to write assertions about the log entries that would have been made, and will aso avoid the performance hit of accessing the database to store the log entries.

Activity logging can be mocked out, or "faked", in the current request by calling

```php
activity()->fake();
```

After this log entries will not be written to the database, but you can make assertions about what _would_ have been logged.

```php

activity()->fake();

activity()->log('Look, ma! No database!');
activity()->log('I can do this all day');

activity()->assertLogged(2);
activity()->assertLoggedWithDescription('Look, ma! No database!', 1);

```

## Assertions

In a phpunit unit testing context, you can make assertions about what _would_ have been logged, after calling `activity()->fake()`.

You can use these assertions to test whether log entries would have been made, and optionally how many, based on a range of different criteria. The final assertion lets you define your own criteria by passing a callback.

In each case, `$expectedCount` is optional: if you omit it, you are asserting that some (i.e. more than zero) log entries were made that meet the relevant criteria. If you include it, you are asserting that exactly that many log entries were made.

`assertLogged($expectedCount = null)`

Asserts that some activities were logged.

`assertNothingLogged()`

Asserts that no activities were logged.

`assertLoggedToLog(string $logName, int $expectedCount = null)`

`assertNothingLoggedToLog(string $logName)`

Asserts that some/no activities were logged to the specified log.

`assertLoggedWithDescription(string $description, int $expectedCount = null)`

`assertNothingLoggedWithDescription(string $description)`

Asserts that some/no activities were logged with the specified description.

`assertLoggedWithEvent(string $event, int $expectedCount = null)`

`assertNothingLoggedWithEvent(string $event)`

Asserts that some/no activities were logged with the specified event.

`assertLoggedWithSubjectType(string $subjectType, int $expectedCount = null)`

`assertNothingLoggedWithSubjectType(string $subjectType)`

Asserts that some/no activities were logged with the specified subject type.

`assertLoggedWithSubjectId(mixed $subjectId, int $expectedCount = null)`

`assertNothingLoggedWithSubjectId(mixed $subjectId)`

Asserts that no activities were logged with the specified subject id.

`assertLoggedWithCauserType(string $causerType, int $expectedCount = null)`

`assertNothingLoggedWithCauserType(string $causerType)`

Asserts that some/no activities were logged with the specified causer type.

`assertLoggedWithCauserId(mixed $causerId, int $expectedCount = null)`

`assertNothingLoggedWithCauserId(mixed $causerId)`

Asserts that some/no activities were logged with the specified causer id.

`assertLoggedWithProperties(mixed $properties, int $expectedCount = null)`

`assertNothingLoggedWithProperties(mixed $properties)`

Asserts that some/no activities were logged with a set of properties that exactly matches the set of specified properties.

`assertLoggedIncludingProperties(mixed $properties, int $expectedCount = null)`

`assertNothingLoggedIncludingProperties(mixed $properties)`

Asserts that some/no activities were logged with a set of properties that includes the specified properties.

`assertLoggedMatching(Closure $callback, int $expectedCount = null)`

`assertNothingLoggedMatching(Closure $callback)`

Asserts that some/no activities were logged that match the criteria determined by the callback.

The callback will be called once for each activity that was logged. It will receive a single `Activity` instance as its argument. It should return true if that activity matches the criteria, false otherwise.

e.g. to assert that at least one activity was logged with a description of 'Foo' and a subject type of 'Bar':

```php
activity()->assertLoggedMatching(function (Activity $activity) {
    return $activity->description === 'Foo' && $activity->subject_type === 'Bar';
});
```
