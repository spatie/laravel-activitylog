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
    public function it_will_log_the_creation_of_the_model()
    {
        $article = $this->createArticle();
        $this->assertCount(1, Activity::all());

        $firstActivity = Activity::first();

        $this->assertInstanceOf(get_class($this->article), $firstActivity->subject);
        $this->assertEquals($article->id, $firstActivity->subject->id);
        $this->assertEquals('created', $firstActivity->description);
    }

    /** @test */
    public function it_will_log_an_update_of_the_model()
    {
        $article = $this->createArticle();

        $article->name = 'changed name';
        $article->save();

        $this->assertCount(2, Activity::all());

        $lastActivity = Activity::all()->last();
        $this->assertInstanceOf(get_class($this->article), $lastActivity->subject);
        $this->assertEquals($article->id, $lastActivity->subject->id);
        $this->assertEquals('updated', $lastActivity->description);

    }

    protected function createArticle(): Article
    {
        $article = new $this->article();
        $article->name = 'my name';
        $article->save();

        return $article;
    }
}