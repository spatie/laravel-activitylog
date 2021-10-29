<?php

namespace Spatie\Activitylog\Test;

use Spatie\Activitylog\Test\Models\ArticleWithLogDescriptionClosure;

class LogsActivitySerializationTest extends TestCase
{
    /** @test */
    public function it_can_be_serialized()
    {
        $model = ArticleWithLogDescriptionClosure::create(['name' => 'foo']);
        
        $this->assertNotNull(serialize($model));
        
    }
}
