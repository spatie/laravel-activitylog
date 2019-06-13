---
title: Using placeholders
weight: 2
---

When logging an activity you may use placeholders that start with `:subject`, `:causer` or `:properties`. These placeholders will get replaced with the properties of the given subject, causer or property.

Here's an example:

```php
activity()
    ->performedOn($article)
    ->causedBy($user)
    ->withProperties(['laravel' => 'awesome'])
    ->log('The subject name is :subject.name, the causer name is :causer.name and Laravel is :properties.laravel');

$lastActivity = Activity::all()->last();
$lastActivity->description; //returns 'The subject name is article name, the causer name is user name and Laravel is awesome';
```
