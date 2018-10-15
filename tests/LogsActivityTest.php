<?php

namespace Spatie\Activitylog\Test;

use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Test\Models\User;
use Spatie\Activitylog\Test\Models\Article;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class LogsActivityTest extends TestCase
{
    /** @var \Spatie\Activitylog\Test\Models\Article|\Spatie\Activitylog\Traits\LogsActivity */
    protected $article;
    /** @var \Spatie\Activitylog\Test\Models\User|\Spatie\Activitylog\Traits\LogsActivity */
    protected $user;

    public function setUp()
    {
        parent::setUp();

        $this->article = new class() extends Article {
            use LogsActivity;
            use SoftDeletes;
        };

        $this->user = new class() extends User {
            use LogsActivity;
            use SoftDeletes;
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
    public function it_can_skip_logging_model_events_if_asked_to()
    {
        $article = new $this->article();
        $article->disableLogging();
        $article->name = 'my name';
        $article->save();

        $this->assertCount(0, Activity::all());
        $this->assertNull($this->getLastActivity());
    }

    /** @test */
    public function it_can_switch_on_activity_logging_after_disabling_it()
    {
        $article = new $this->article();

        $article->disableLogging();
        $article->name = 'my name';
        $article->save();

        $article->enableLogging();
        $article->name = 'my new name';
        $article->save();

        $this->assertCount(1, Activity::all());
        $this->assertInstanceOf(get_class($this->article), $this->getLastActivity()->subject);
        $this->assertEquals($article->id, $this->getLastActivity()->subject->id);
        $this->assertEquals('updated', $this->getLastActivity()->description);
    }

    /** @test */
    public function it_can_skip_logging_if_asked_to_for_update_method()
    {
        $article = new $this->article();
        $article->disableLogging()->update(['name' => 'How to log events']);

        $this->assertCount(0, Activity::all());
        $this->assertNull($this->getLastActivity());
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
    public function it_will_log_the_deletion_of_a_model_without_softdeletes()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;
        };

        $article = new $articleClass();

        $article->save();

        $this->assertEquals('created', $this->getLastActivity()->description);

        $article->delete();

        $this->assertEquals('deleted', $this->getLastActivity()->description);
    }

    /** @test */
    public function it_will_log_the_deletion_of_a_model_with_softdeletes()
    {
        $article = $this->createArticle();

        $article->delete();

        $this->assertCount(2, Activity::all());

        $this->assertEquals(get_class($this->article), $this->getLastActivity()->subject_type);
        $this->assertEquals($article->id, $this->getLastActivity()->subject_id);
        $this->assertEquals('deleted', $this->getLastActivity()->description);

        $article->forceDelete();

        $this->assertCount(3, Activity::all());

        $this->assertEquals('deleted', $this->getLastActivity()->description);
        $this->assertNull($article->fresh());
    }

    /** @test */
    public function it_will_log_the_restoring_of_a_model_with_softdeletes()
    {
        $article = $this->createArticle();

        $article->delete();

        $article->restore();

        $this->assertCount(3, Activity::all());

        $this->assertEquals(get_class($this->article), $this->getLastActivity()->subject_type);
        $this->assertEquals($article->id, $this->getLastActivity()->subject_id);
        $this->assertEquals('restored', $this->getLastActivity()->description);
    }

    /** @test */
    public function it_can_fetch_all_activity_for_a_model()
    {
        $article = $this->createArticle();

        $article->name = 'changed name';
        $article->save();

        $activities = $article->activities;

        $this->assertCount(2, $activities);
    }

    /** @test */
    public function it_can_fetch_soft_deleted_models()
    {
        $this->app['config']->set('activitylog.subject_returns_soft_deleted_models', true);

        $article = $this->createArticle();

        $article->name = 'changed name';
        $article->save();

        $article->delete();

        $activities = $article->activities;

        $this->assertCount(3, $activities);

        $this->assertEquals(get_class($this->article), $this->getLastActivity()->subject_type);
        $this->assertEquals($article->id, $this->getLastActivity()->subject_id);
        $this->assertEquals('deleted', $this->getLastActivity()->description);
        $this->assertEquals('changed name', $this->getLastActivity()->subject->name);
    }

    /** @test */
    public function it_can_log_activity_to_log_returned_from_model_method_override()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            public function getLogNameToUse()
            {
                return 'custom_log';
            }
        };

        $article = new $articleClass();
        $article->name = 'my name';
        $article->save();

        $this->assertEquals($article->id, Activity::inLog('custom_log')->first()->subject->id);
        $this->assertCount(1, Activity::inLog('custom_log')->get());
    }

    /** @test */
    public function it_can_log_activity_to_log_named_in_the_model()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            protected static $logName = 'custom_log';
        };

        $article = new $articleClass();
        $article->name = 'my name';
        $article->save();

        $this->assertSame('custom_log', Activity::latest()->first()->log_name);
    }

    /** @test */
    public function it_will_not_log_an_update_of_the_model_if_only_ignored_attributes_are_changed()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            protected static $ignoreChangedAttributes = ['text'];
        };

        $article = new $articleClass();
        $article->name = 'my name';
        $article->save();

        $article->text = 'ignore me';
        $article->save();

        $this->assertCount(1, Activity::all());

        $this->assertInstanceOf(get_class($articleClass), $this->getLastActivity()->subject);
        $this->assertEquals($article->id, $this->getLastActivity()->subject->id);
        $this->assertEquals('created', $this->getLastActivity()->description);
    }

    /** @test */
    public function it_will_not_fail_if_asked_to_replace_from_empty_attribute()
    {
        $model = new class() extends Article {
            use LogsActivity;
            use SoftDeletes;

            public function getDescriptionForEvent(string $eventName): string
            {
                return ":causer.name $eventName";
            }
        };

        $entity = new $model();
        $entity->save();
        $entity->name = 'my name';
        $entity->save();

        $activities = $entity->activities;

        $this->assertCount(2, $activities);
        $this->assertEquals($entity->id, $activities[0]->subject->id);
        $this->assertEquals($entity->id, $activities[1]->subject->id);
        $this->assertEquals(':causer.name created', $activities[0]->description);
        $this->assertEquals(':causer.name updated', $activities[1]->description);
    }

    /** @test */
    public function it_can_log_activity_on_subject_by_same_causer()
    {
        $user = $this->loginWithFakeUser();

        $user->name = 'LogsActivity Name';
        $user->save();

        $this->assertCount(1, Activity::all());

        $this->assertInstanceOf(get_class($this->user), $this->getLastActivity()->subject);
        $this->assertEquals($user->id, $this->getLastActivity()->subject->id);
        $this->assertEquals($user->id, $this->getLastActivity()->causer->id);
        $this->assertCount(1, $user->activities);
        $this->assertCount(1, $user->actions);
    }

    public function loginWithFakeUser()
    {
        $user = new $this->user();

        $user::find(1);

        $this->be($user);

        return $user;
    }

    protected function createArticle(): Article
    {
        $article = new $this->article();
        $article->name = 'my name';
        $article->save();

        return $article;
    }
}
