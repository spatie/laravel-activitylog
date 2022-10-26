<?php

namespace Spatie\Activitylog\Test;

use Spatie\Activitylog\Test\Models\ArticleWithLogDescriptionClosure;

it('can_be_serialized', function () {
    $model = ArticleWithLogDescriptionClosure::create(['name' => 'foo']);

    $this->assertNotNull(serialize($model));
});
