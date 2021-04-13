<?php

namespace Spatie\Activitylog\Test;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Test\Models\Article;
use Spatie\Activitylog\Test\Models\Issue733;
use Spatie\Activitylog\Test\Models\User;
use Spatie\Activitylog\Traits\LogsActivity;

class LogsActivityTest extends TestCase
{
    /** @var \Spatie\Activitylog\Test\Models\Article|\Spatie\Activitylog\Traits\LogsActivity */
    protected $article;
    /** @var \Spatie\Activitylog\Test\Models\User|\Spatie\Activitylog\Traits\LogsActivity */
    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->article = new class() extends Article {
            use LogsActivity;
            use SoftDeletes;

            public function getActivitylogOptions() : LogOptions
            {
                return LogOptions::defaults();
            }
        };

        $this->user = new class() extends User {
            use LogsActivity;
            use SoftDeletes;


            public function getActivitylogOptions() : LogOptions
            {
                return LogOptions::defaults();
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
        $this->assertEquals('created', $this->getLastActivity()->event);
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
        $this->assertEquals('updated', $this->getLastActivity()->event);
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
        $this->assertEquals('updated', $this->getLastActivity()->event);
    }

    /** @test */
    public function it_will_log_the_deletion_of_a_model_without_softdeletes()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            public function getActivitylogOptions() : LogOptions
            {
                return LogOptions::defaults()->logOnly(['name']);
            }
        };

        $article = new $articleClass();
        $article->name = 'my name';
        $article->save();

        $this->assertEquals('created', $this->getLastActivity()->description);
        $this->assertEquals('created', $this->getLastActivity()->event);

        $article->delete();

        $activity = $this->getLastActivity();

        $this->assertEquals('deleted', $activity->description);
        $this->assertArrayHasKey('old', $activity->changes());
        $this->assertEquals('my name', $activity->changes()['old']['name']);
        $this->assertArrayNotHasKey('attributes', $activity->changes());

        $this->assertEquals('deleted', $activity->description);
        $this->assertEquals('deleted', $activity->event);
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
        $this->assertEquals('deleted', $this->getLastActivity()->event);

        $article->forceDelete();

        $this->assertCount(3, Activity::all());

        $this->assertEquals('deleted', $this->getLastActivity()->description);
        $this->assertEquals('deleted', $this->getLastActivity()->event);
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
        $this->assertEquals('restored', $this->getLastActivity()->event);
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
        $this->assertEquals('deleted', $this->getLastActivity()->event);
        $this->assertEquals('changed name', $this->getLastActivity()->subject->name);
    }

    /** remove this test? */
    // public function it_can_log_activity_to_log_returned_from_model_method_override()
    // {
    //     $articleClass = new class() extends Article {
    //         use LogsActivity;

    //         public function getActivitylogOptions() : ActivitylogOptions
    //         {
    //             return ActivitylogOptions::defaults()
    //             ->useLogName('custom_log');
    //         }
    //     };

    //     $article = new $articleClass();
    //     $article->name = 'my name';
    //     $article->save();

    //     $this->assertEquals($article->id, Activity::inLog('custom_log')->first()->subject->id);
    //     $this->assertCount(1, Activity::inLog('custom_log')->get());
    // }

    /** @test */
    public function it_can_log_activity_to_log_named_in_the_model()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            public function getActivitylogOptions() : LogOptions
            {
                return LogOptions::defaults()
                ->useLogName('custom_log');
            }
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

            public function getActivitylogOptions() : LogOptions
            {
                return LogOptions::defaults()
                ->dontLogIfAttributesChangedOnly([ 'text']);
            }
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
        $this->assertEquals('created', $this->getLastActivity()->event);
    }

    /** @test */
    public function it_will_not_fail_if_asked_to_replace_from_empty_attribute()
    {
        $model = new class() extends Article {
            use LogsActivity;
            use SoftDeletes;

            public function getActivitylogOptions() : LogOptions
            {
                return LogOptions::defaults()
                ->setDescriptionForEvent(fn (string $eventName):string => ":causer.name $eventName");
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

    /** @test */
    public function it_can_log_activity_when_attributes_are_changed_with_tap()
    {
        $model = new class() extends Article {
            use LogsActivity;

            public function getActivitylogOptions() : LogOptions
            {
                return LogOptions::defaults();
            }

            protected $properties = [
                'property' => [
                    'subProperty' => 'value',
                ],
            ];

            public function tapActivity(Activity $activity, string $eventName)
            {
                $properties = $this->properties;
                $properties['event'] = $eventName;
                $activity->properties = collect($properties);
                $activity->created_at = Carbon::yesterday()->startOfDay();
            }
        };

        $entity = new $model();
        $entity->save();

        $firstActivity = $entity->activities()->first();

        $this->assertInstanceOf(Collection::class, $firstActivity->properties);
        $this->assertEquals('value', $firstActivity->getExtraProperty('property.subProperty'));
        $this->assertEquals('created', $firstActivity->description);
        $this->assertEquals('created', $firstActivity->event);
        $this->assertEquals(Carbon::yesterday()->startOfDay()->format('Y-m-d H:i:s'), $firstActivity->created_at->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_can_log_activity_when_description_is_changed_with_tap()
    {
        $model = new class() extends Article {
            use LogsActivity;

            public function getActivitylogOptions() : LogOptions
            {
                return LogOptions::defaults();
            }

            public function tapActivity(Activity $activity, string $eventName)
            {
                $activity->description = 'my custom description';
            }
        };

        $entity = new $model();
        $entity->save();

        $firstActivity = $entity->activities()->first();

        $this->assertEquals('my custom description', $firstActivity->description);
    }


    /** @test */
    public function it_can_log_activity_when_event_is_changed_with_tap()
    {
        $model = new class() extends Article {
            use LogsActivity;

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults();
            }

            public function tapActivity(Activity $activity, string $eventName)
            {
                $activity->event = 'my custom event';
            }
        };

        $entity = new $model();
        $entity->save();

        $firstActivity = $entity->activities()->first();

        $this->assertEquals('my custom event', $firstActivity->event);
    }

    /** @test */
    public function it_will_not_submit_log_when_there_is_no_changes()
    {
        $model = new class() extends Article {
            use LogsActivity;

            public function getActivitylogOptions() : LogOptions
            {
                return LogOptions::defaults()
                ->logOnly(['text'])
                ->dontSubmitEmptyLogs()
                ->logOnlyDirty();
            }
        };

        $entity = new $model(['text' => 'test']);
        $entity->save();

        $this->assertCount(1, Activity::all());

        $entity->name = 'my name';
        $entity->save();

        $this->assertCount(1, Activity::all());
    }

    /** @test */
    public function it_will_submit_a_log_with_json_changes()
    {
        $model = new class() extends Article {
            use LogsActivity;

            protected $casts = [
                'json' => 'collection',
            ];

            public function getActivitylogOptions() : LogOptions
            {
                return LogOptions::defaults()
                ->logOnly(['text', 'json->data'])
                ->dontSubmitEmptyLogs()
                ->logOnlyDirty();
            }
        };

        $entity = new $model([
            'text' => 'test',
            'json' => [
                'data' => 'oldish',
            ],
        ]);

        $entity->save();

        $this->assertCount(1, Activity::all());

        $entity->json = [
            'data' => 'chips',
            'irrelevant' => 'should not be',
        ];

        $entity->save();

        $expectedChanges = [
            'attributes' => [
                'json' => [
                    'data' => 'chips',
                ],
            ],
            'old' => [
                'json' => [
                    'data' => 'oldish',
                ],
            ],
        ];

        $changes = $this->getLastActivity()->changes()->toArray();

        $this->assertCount(2, Activity::all());
        $this->assertSame($expectedChanges, $changes);
    }

    /** @test */
    public function it_will_log_the_retrieval_of_the_model()
    {
        $article = Issue733::create(['name' => 'my name']);

        $retrieved = Issue733::whereKey($article->getKey())->first();
        $this->assertTrue($article->is($retrieved));

        $activity = $this->getLastActivity();

        $this->assertInstanceOf(get_class($article), $activity->subject);
        $this->assertTrue($article->is($activity->subject));
        $this->assertEquals('retrieved', $activity->description);
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
