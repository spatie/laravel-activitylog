---
title: Event Bag
weight: 4
---

This class will carry changes over the user defined pipes via `handle` method and will have these properties:

```php
// Event name (string or ActivityEvent enum)
public string|ActivityEvent $event,

// Model in question
public Model $model,

// Changes array
public array $changes,

// Current applied options
public ?LogOptions $options = null
```

Please don't attempt to change the model inside the event since it may result in unexpected results or miss with the logs.
