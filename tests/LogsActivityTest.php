<?php

namespace Spatie\Activitylog\Test;

use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Test\Models\Article;
use Spatie\Activitylog\Traits\LogsActivity;

class LogsActivityTest extends TestCase
{
    /** @var \Spatie\Activitylog\Test\Article|\Spatie\Activitylog\Traits\LogsActivity  */
    protected $article;
    protected $articleWithCustomLog;

    public function setUp()
    {
        parent::setUp();

        $this->article = new class extends Article {
            use LogsActivity;
        };

        $this->articleWithCustomLog = new class extends Article {
            use LogsActivity;

            public function getLogToUse()
            {
                return 'custom_log';
            }
        };

        $this->assertCount(0, Activity::all());
    }

    /** @test */
    public function it_will_log_the_creation_of_the_model()
    {
        $article = $this->createArticle();
        $this->assertCount(1, Activity::all());

        $this->assertInstanceOf(get_class($this->article), $this->getLastActivity()->subject);
        $this->assertEquals($article->id, $this->getLastActivity()->subject->id);
        $this->assertEquals('created', $this->getLastActivity()->description);
    }

    /** @test */
    public function it_will_log_an_update_of_the_model()
    {
        $article = $this->createArticle();

        $article->name = 'changed name';
        $article->save();

        $this->assertCount(2, Activity::all());

        $this->assertInstanceOf(get_class($this->article), $this->getLastActivity()->subject);
        $this->assertEquals($article->id, $this->getLastActivity()->subject->id);
        $this->assertEquals('updated', $this->getLastActivity()->description);
    }

    /** @test */
    public function it_will_log_the_deletion_of_the_model()
    {
        $article = $this->createArticle();

        $article->delete();

        $this->assertCount(2, Activity::all());

        $this->assertEquals(get_class($this->article), $this->getLastActivity()->subject_type);
        $this->assertEquals($article->id, $this->getLastActivity()->subject_id);
        $this->assertEquals('deleted', $this->getLastActivity()->description);
    }

    /** @test */
    public function it_can_fetch_all_activity_for_a_model()
    {
        $article = $this->createArticle();

        $article->name = 'changed name';
        $article->save();

        $activities = $article->activity;

        $this->assertCount(2, $activities);
    }

    /** @test */
    public function it_can_log_activity_to_log_named_in_the_model()
    {
        $article = $this->createArticle();
        $articleWithCustomLog = $this->createArticleWithCustomLog();
        $this->assertEquals('default', $article->getLogToUse());
        $this->assertEquals('custom_log', $articleWithCustomLog->getLogToUse());
        $this->assertEquals($article->id, Activity::inLog('default')->first()->subject->id);
        $this->assertEquals($articleWithCustomLog->id, Activity::inLog('custom_log')->first()->subject->id);
        $this->assertCount(1, Activity::inLog('default')->get());
        $this->assertCount(1, Activity::inLog('custom_log')->get());
    }


    protected function createArticle(): Article
    {
        $article = new $this->article();
        $article->name = 'my name';
        $article->save();

        return $article;
    }

    protected function createArticleWithCustomLog(): Article
    {
        $article = new $this->articleWithCustomLog();
        $article->name = 'my name';
        $article->save();

        return $article;
    }
    
}
