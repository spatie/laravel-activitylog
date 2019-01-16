## From v2 to v3

- if you are using a custom `Activity` model, you should let it implement the new `Spatie\Activitylog\Contracts\Activity` interface
- the preferred way to get changes on an `Activity` model is through the `changes` property instead of the `changes()` function. Change all usages from
`$activity->changes()` to `$activity->changes`
- the `activity` relation of the `CausesActivity` trait has been renamed to `actions`.  Rename all uses from `$user->activity` to `$user->actions`
- the `activity` relation of the `LogsActivity` trait has been renamed to `activities`. Rename all uses from `$yourModel->activity` to `$yourModel->activities`.
- the deprecated `loggedActivity` relation has been removed. Use `activities` instead.
- the `HasActivity` trait has been removed. Use both `CausesActivity` and `LogsActivity` traits instead.