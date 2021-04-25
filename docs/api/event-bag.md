---
title: Event Bag
weight: 4
---

This class will carry changes over the user defined pipes via `handel` method and will have these properties:

```php
        // Event Name
        public string $event,

        // Model in question
        public Model $model,

        // changes array
        public array $changes,

        // current applied options
        public ?LogOptions $options = null

```

Please don't attempt to change the model inside the event since it may result in unexpected results or miss with the logs.
