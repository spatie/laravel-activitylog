<?php

namespace Spatie\Activitylog\Test;

use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Test\Models\Article;
use Spatie\Activitylog\Traits\LogsActivity;

class CausesActivityTest extends TestCase
{
    /** @var \Spatie\Activitylog\Test\Article|\Spatie\Activitylog\Traits\LogsActivity  */
    protected $article;
    
    public function setUp()
    {
        parent::setUp();
        
        $this->article = new class extends Article {
            use LogsActivity;
        };
        
        $this->assertCount(0, Activity::all());
    }

    /** @test */
    public function it_will_log_when_the_model_is_created()
    {
        $article = new $this->article();
        $article->name = 'my name';
        $article->save();

        $this->assertCount(1, Activity::all());

        
    }
}