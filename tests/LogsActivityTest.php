<?php

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Test\Models\Article;
use Spatie\Activitylog\Test\Models\Issue733;
use Spatie\Activitylog\Test\Models\User;
use Spatie\Activitylog\Traits\LogsActivity;

beforeEach(function () {
    $this->article = new class() extends Article {
        use LogsActivity;
        use SoftDeletes;

        public function getActivitylogOptions(): LogOptions
        {
            return LogOptions::defaults();
        }
    };

    $this->user = new class() extends User {
        use LogsActivity;
        use SoftDeletes;

        public function getActivitylogOptions(): LogOptions
        {
            return LogOptions::defaults();
        }
    };

    $this->assertCount(0, Activity::all());
});

it('will log the creation of the model', function () {
    $article = $this->createArticle();
    $this->assertCount(1, Activity::all());

    $this->assertInstanceOf(get_class($this->article), $this->getLastActivity()->subject);
    $this->assertEquals($article->id, $this->getLastActivity()->subject->id);
    $this->assertEquals('created', $this->getLastActivity()->description);
    $this->assertEquals('created', $this->getLastActivity()->event);
});

it('can skip logging model events if asked to', function () {
    $article = new $this->article();
    $article->disableLogging();
    $article->name = 'my name';

    $article->save();

    $this->assertCount(0, Activity::all());
    $this->assertNull($this->getLastActivity());
});

it('can switch on activity logging after disabling it', function () {
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
});

it('can skip logging if asked to for update method', function () {
    $article = new $this->article();
    $article->disableLogging()->update(['name' => 'How to log events']);

    $this->assertCount(0, Activity::all());
    $this->assertNull($this->getLastActivity());
});

it('will log an update of the model', function () {
    $article = $this->createArticle();

    $article->name = 'changed name';
    $article->save();

    $this->assertCount(2, Activity::all());

    $this->assertInstanceOf(get_class($this->article), $this->getLastActivity()->subject);
    $this->assertEquals($article->id, $this->getLastActivity()->subject->id);
    $this->assertEquals('updated', $this->getLastActivity()->description);
    $this->assertEquals('updated', $this->getLastActivity()->event);
});

it('will log the restoring of a model with softdeletes', function () {
    $article = $this->createArticle();

    $replicatedArticle = $this->article::find($article->id)->replicate();
    $replicatedArticle->save();

    $activityItems = Activity::all();

    $this->assertCount(2, $activityItems);

    $this->assertTrue($activityItems->every(fn (Activity $item) =>
        $item->event === 'created' &&
        $item->description === 'created' &&
        get_class($this->article) === $item->subject_type));

    $this->assertEquals($replicatedArticle->id, $this->getLastActivity()->subject_id);
});

it('will log the deletion of a model without softdeletes', function () {
    $articleClass = new class() extends Article {
        use LogsActivity;

        public function getActivitylogOptions(): LogOptions
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
});

it('will log the deletion of a model with softdeletes', function () {
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
});

it('will log the restoring of a model with softdeletes', function () {
    $article = $this->createArticle();

    $article->delete();

    $article->restore();

    $this->assertCount(3, Activity::all());

    $this->assertEquals(get_class($this->article), $this->getLastActivity()->subject_type);
    $this->assertEquals($article->id, $this->getLastActivity()->subject_id);
    $this->assertEquals('restored', $this->getLastActivity()->description);
    $this->assertEquals('restored', $this->getLastActivity()->event);
});

it('can fetch all activity for a model', function () {
    $article = $this->createArticle();

    $article->name = 'changed name';
    $article->save();

    $activities = $article->activities;

    $this->assertCount(2, $activities);
});

it('can fetch soft deleted models', function () {
    app()['config']->set('activitylog.subject_returns_soft_deleted_models', true);

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
});

it('can log activity to log named in the model', function () {
    $articleClass = new class() extends Article {
        use LogsActivity;

        public function getActivitylogOptions(): LogOptions
        {
            return LogOptions::defaults()
            ->useLogName('custom_log');
        }
    };

    $article = new $articleClass();
    $article->name = 'my name';
    $article->save();

    $this->assertSame('custom_log', Activity::latest()->first()->log_name);
});

it('will not log an update of the model if only ignored attributes are changed', function () {
    $articleClass = new class() extends Article {
        use LogsActivity;

        public function getActivitylogOptions(): LogOptions
        {
            return LogOptions::defaults()
            ->dontLogIfAttributesChangedOnly(['text']);
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
});

it('will not fail if asked to replace from empty attribute', function () {
    $model = new class() extends Article {
        use LogsActivity;
        use SoftDeletes;

        public function getActivitylogOptions(): LogOptions
        {
            return LogOptions::defaults()
            ->setDescriptionForEvent(fn (string $eventName): string => ":causer.name $eventName");
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
});

it('can log activity on subject by same causer', function () {
    $user = $this->loginWithFakeUser();

    $user->name = 'LogsActivity Name';
    $user->save();

    $this->assertCount(1, Activity::all());

    $this->assertInstanceOf(get_class($this->user), $this->getLastActivity()->subject);
    $this->assertEquals($user->id, $this->getLastActivity()->subject->id);
    $this->assertEquals($user->id, $this->getLastActivity()->causer->id);
    $this->assertCount(1, $user->activities);
    $this->assertCount(1, $user->actions);
});

it('can log activity when attributes are changed with tap', function () {
    $model = new class() extends Article {
        use LogsActivity;

        public function getActivitylogOptions(): LogOptions
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
});

it('can log activity when description is changed with tap', function () {
    $model = new class() extends Article {
        use LogsActivity;

        public function getActivitylogOptions(): LogOptions
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
});

it('can log activity when event is changed with tap', function () {
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
});

it('will not submit log when there is no changes', function () {
    $model = new class() extends Article {
        use LogsActivity;

        public function getActivitylogOptions(): LogOptions
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
});

it('will submit a log with json changes', function () {
    $model = new class() extends Article {
        use LogsActivity;

        protected $casts = [
            'json' => 'collection',
        ];

        public function getActivitylogOptions(): LogOptions
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
});

it('will log the retrieval of the model', function () {
    $article = Issue733::create(['name' => 'my name']);

    $retrieved = Issue733::whereKey($article->getKey())->first();
    $this->assertTrue($article->is($retrieved));

    $activity = $this->getLastActivity();

    $this->assertInstanceOf(get_class($article), $activity->subject);
    $this->assertTrue($article->is($activity->subject));
    $this->assertEquals('retrieved', $activity->description);
});

it('will not log casted attribute of the model if attribute raw values is used', function () {
    $articleClass = new class() extends Article {
        use LogsActivity;

        protected $casts = [
            'name' => 'encrypted',
        ];

        public function getActivitylogOptions(): LogOptions
        {
            return LogOptions::defaults()->logOnly(['name'])->useAttributeRawValues(['name']);
        }
    };

    $article = new $articleClass();
    $article->name = 'my name';
    $article->save();

    $this->assertInstanceOf(get_class($articleClass), $this->getLastActivity()->subject);
    $this->assertEquals($article->id, $this->getLastActivity()->subject->id);
    $this->assertNotEquals($article->name, $this->getLastActivity()->properties['attributes']['name']);
    $this->assertEquals('created', $this->getLastActivity()->description);
    $this->assertEquals('created', $this->getLastActivity()->event);
});
