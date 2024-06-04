---
title: Altering comparison behavior
weight: 7
---

By default, the spaceship operator is used to compare 2 objects with each other.
This does not always result in what we expect.

For instance, when using the `laravel-model-states` package, a copy of the model is stored per instance of the state.
Causing the state to be logged as a change even when they appear the same.

We can adjust the way objects are being compared by implementing the `Compareable` interface.
This will provide us with a `compareTo` function that can tweak the comparison logic.

```php
use Spatie\Activitylog\Contracts\Compareable;

abstract class OrderState extends State implements Compareable
{
    public function compareTo(Compareable $compareable): int
    {
        if (! $compareable instanceof State) {
            return 1;
        }

        if (get_class($this) !== get_class($compareable)) {
            return 1;
        };
        
        return 0;
    }
}
```

This way no changes are being logged when you decide 2 objects are equal to each other.
