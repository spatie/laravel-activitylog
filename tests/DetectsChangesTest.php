<?php

namespace Spatie\Activitylog\Test;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Closure;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Spatie\Activitylog\Contracts\LoggablePipe;
use Spatie\Activitylog\EventLogBag;
use Spatie\Activitylog\LogBatch;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Test\Casts\IntervalCasts;
use Spatie\Activitylog\Test\Models\Article;
use Spatie\Activitylog\Test\Models\User;
use Spatie\Activitylog\Traits\LogsActivity;

class DetectsChangesTest extends TestCase
{
    /** @var \Spatie\Activitylog\Test\Models\Article|\Spatie\Activitylog\Traits\LogsActivity */
    protected $article;

    public function setUp(): void
    {
        parent::setUp();

        $this->article = new class() extends Article {
            use LogsActivity;

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logOnly(['name', 'text']);
            }
        };

        $this->assertCount(0, Activity::all());
    }

    /** @test */
    public function it_can_store_the_values_when_creating_a_model()
    {
        $this->createArticle();

        $expectedChanges = [
            'attributes' => [
                'name' => 'my name',
                'text' => null,
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_deep_diff_check_json_field()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            protected $casts = [
                'json' => 'collection',
            ];

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->dontSubmitEmptyLogs()
                ->logOnlyDirty()
                ->logOnly(['json->phone', 'json->details', 'json->address']);
            }
        };

        $articleClass::addLogChange(new class() implements LoggablePipe {
            public function handle(EventLogBag $event, Closure $next): EventLogBag
            {
                if ($event->event === 'updated') {
                    $event->changes['attributes']['json'] = array_udiff_assoc(
                        $event->changes['attributes']['json'],
                        $event->changes['old']['json'],
                        function ($new, $old) {
                            if ($old === null || $new === null) {
                                return 0;
                            }

                            return $new <=> $old;
                        }
                    );

                    $event->changes['old']['json'] = collect($event->changes['old']['json'])
                    ->only(array_keys($event->changes['attributes']['json']))
                    ->all();
                }

                return $next($event);
            }
        });

        $article = $articleClass::create([
            'name' => 'Hamburg',
            'json' => ['details' => '', 'phone' => '1231231234', 'address' => 'new address'],
          ]);

        $article->update(['json' => ['details' => 'new details']]);

        $expectedChanges = [
            'attributes' => [
                'json' => [
                    'details' => 'new details',
                ],
            ],
            'old' => [
                'json' => [
                    'details' => '',
                ],
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_detect_changes_for_date_inteval_attributes()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            protected $casts = [
                'interval' => IntervalCasts::class,
            ];

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logOnly(['name', 'interval'])
                ->logOnlyDirty();
            }
        };

        $article = $articleClass::create([
            'name' => 'Hamburg',
            'interval' => CarbonInterval::minute(),
          ]);

        $article->update(['name' => 'New name', 'interval' => CarbonInterval::month()]);

        $expectedChanges = [
            'attributes' => [
                'name' => 'New name',
                'interval' => '1 month',
            ],
            'old' => [
                'name' => 'Hamburg',
                'interval' => '1 minute',
            ],
        ];

        // test case when intervals changing from interval to another
        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_detect_changes_for_null_date_inteval_attributes()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            protected $casts = [
                    'interval' => IntervalCasts::class,
                ];

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                    ->logAll()
                    ->dontLogIfAttributesChangedOnly(['created_at', 'updated_at', 'deleted_at'])
                    ->logOnlyDirty();
            }
        };

        $nullIntevalArticle = $articleClass::create([
                'name' => 'Hamburg',
              ]);

        $nullIntevalArticle->update(['name' => 'New name', 'interval' => CarbonInterval::month()]);

        $expectedChangesForNullInterval = [
                'attributes' => [
                    'name' => 'New name',
                    'interval' => '1 month',
                ],
                'old' => [
                    'name' => 'Hamburg',
                    'interval' => null,
                ],
            ];
        $this->assertEquals($expectedChangesForNullInterval, $this->getLastActivity()->changes()->toArray());

        $intervalArticle = $articleClass::create([
            'name' => 'Hamburg',
            'interval' => CarbonInterval::month(),
          ]);

        $intervalArticle->update(['name' => 'New name', 'interval' => null]);

        $expectedChangesForInterval = [
                'attributes' => [
                    'name' => 'New name',
                    'interval' => null,
                ],
                'old' => [
                    'name' => 'Hamburg',
                    'interval' => '1 month',
                ],
            ];

        $this->assertEquals($expectedChangesForInterval, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_store_the_relation_values_when_creating_a_model()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logOnly(['name', 'text', 'user.name']);
            }
        };

        $user = User::create([
            'name' => 'user name',
        ]);

        $article = $articleClass::create([
            'name' => 'original name',
            'text' => 'original text',
            'user_id' => $user->id,
        ]);

        $article->name = 'updated name';
        $article->text = 'updated text';
        $article->save();

        $expectedChanges = [
            'attributes' => [
                'name' => 'updated name',
                'text' => 'updated text',
                'user.name' => 'user name',
            ],
            'old' => [
                'name' => 'original name',
                'text' => 'original text',
                'user.name' => 'user name',
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_retruns_same_uuid_for_all_log_changes_under_one_batch()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;
            use SoftDeletes;

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                    ->logOnly(['name', 'text']);
            }
        };

        app(LogBatch::class)->startBatch();

        $user = User::create([
                'name' => 'user name',
            ]);

        $article = $articleClass::create([
                'name' => 'original name',
                'text' => 'original text',
                'user_id' => $user->id,
            ]);

        $article->name = 'updated name';
        $article->text = 'updated text';
        $article->save();

        $article->delete();
        $article->forceDelete();

        $batchUuid = app(LogBatch::class)->getUuid();

        app(LogBatch::class)->endBatch();

        $this->assertTrue(Activity::pluck('batch_uuid')->every(fn ($uuid) => $uuid === $batchUuid));
    }

    /** @test */
    public function it_assigns_new_uuid_for_multiple_change_logs_in_different_batches()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;
            use SoftDeletes;

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                      ->logOnly(['name', 'text']);
            }
        };

        app(LogBatch::class)->startBatch();

        $uuidForCreatedEvent = app(LogBatch::class)->getUuid();
        $user = User::create([
                  'name' => 'user name',
              ]);

        $article = $articleClass::create([
                  'name' => 'original name',
                  'text' => 'original text',
                  'user_id' => $user->id,
              ]);

        app(LogBatch::class)->endBatch();

        $this->assertTrue(Activity::pluck('batch_uuid')->every(fn ($uuid) => $uuid === $uuidForCreatedEvent));

        app(LogBatch::class)->startBatch();

        $article->name = 'updated name';
        $article->text = 'updated text';
        $article->save();
        $uuidForUpdatedEvents = app(LogBatch::class)->getUuid();

        app(LogBatch::class)->endBatch();

        $this->assertCount(1, Activity::where('description', 'updated')->get());

        $this->assertEquals($uuidForUpdatedEvents, Activity::where('description', 'updated')->first()->batch_uuid);

        app(LogBatch::class)->startBatch();
        $article->delete();
        $article->forceDelete();

        $uuidForDeletedEvents = app(LogBatch::class)->getUuid();

        app(LogBatch::class)->endBatch();

        $this->assertCount(2, Activity::where('batch_uuid', $uuidForDeletedEvents)->get());

        $this->assertNotSame($uuidForCreatedEvent, $uuidForDeletedEvents);
    }

    /** @test */
    public function it_can_removes_key_event_if_it_was_loggable()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logOnly(['name', 'text', 'user.name']);
            }
        };

        $user = User::create([
            'name' => 'user name',
        ]);

        $articleClass::addLogChange(new class() implements LoggablePipe {
            public function handle(EventLogBag $event, Closure $next): EventLogBag
            {
                Arr::forget($event->changes, ['attributes.name', 'old.name']);

                return $next($event);
            }
        });

        $article = $articleClass::create([
            'name' => 'original name',
            'text' => 'original text',
            'user_id' => $user->id,
        ]);

        $article->name = 'updated name';
        $article->text = 'updated text';
        $article->save();

        $expectedChanges = [
            'attributes' => [
                'text' => 'updated text',
                'user.name' => 'user name',
            ],
            'old' => [
                'text' => 'original text',
                'user.name' => 'user name',
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_store_empty_relation_when_creating_a_model()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logOnly(['name', 'text', 'user.name']);
            }
        };

        $user = User::create([
            'name' => 'user name',
        ]);

        $article = $articleClass::create([
            'name' => 'original name',
            'text' => 'original text',
        ]);

        $article->name = 'updated name';
        $article->text = 'updated text';
        $article->save();

        $expectedChanges = [
            'attributes' => [
                'name' => 'updated name',
                'text' => 'updated text',
                'user.name' => null,
            ],
            'old' => [
                'name' => 'original name',
                'text' => 'original text',
                'user.name' => null,
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_store_the_changes_when_updating_a_model()
    {
        $article = $this->createArticle();

        $article->name = 'updated name';
        $article->text = 'updated text';

        $article->save();

        $expectedChanges = [
            'attributes' => [
                'name' => 'updated name',
                'text' => 'updated text',
            ],
            'old' => [
                'name' => 'my name',
                'text' => null,
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_store_dirty_changes_only()
    {
        $article = $this->createDirtyArticle();

        $article->name = 'updated name';

        $article->save();

        $expectedChanges = [
            'attributes' => [
                'name' => 'updated name',
            ],
            'old' => [
                'name' => 'my name',
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_store_dirty_changes_for_swapping_values()
    {
        $article = $this->createDirtyArticle();

        $originalName = $article->name;
        $originalText = $article->text;

        $article->text = $originalName;
        $article->name = $originalText;

        $article->save();

        $expectedChanges = [
            'attributes' => [
                'name' => $originalText,
                'text' => $originalName,
            ],
            'old' => [
                'name' => $originalName,
                'text' => $originalText,
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_store_the_changes_when_updating_a_related_model()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logOnly(['name', 'text', 'user.name']);
            }
        };

        $user = User::create([
            'name' => 'a name',
        ]);

        $anotherUser = User::create([
            'name' => 'another name',
        ]);

        $article = $articleClass::create([
            'name' => 'name',
            'text' => 'text',
            'user_id' => $user->id,
        ]);

        $article->user()->associate($anotherUser)->save();

        $expectedChanges = [
            'attributes' => [
                'name' => 'name',
                'text' => 'text',
                'user.name' => 'another name',
            ],
            'old' => [
                'name' => 'name',
                'text' => 'text',
                'user.name' => 'a name',
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_store_the_changes_when_updating_a_snake_case_related_model()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logOnly(['name', 'text', 'snakeUser.name']);
            }

            public function snake_user()
            {
                return $this->belongsTo(User::class, 'user_id');
            }
        };

        $user = User::create([
            'name' => 'a name',
        ]);

        $anotherUser = User::create([
            'name' => 'another name',
        ]);

        $article = $articleClass::create([
            'name' => 'name',
            'text' => 'text',
            'user_id' => $user->id,
        ]);

        $article->user()->associate($anotherUser)->save();

        $expectedChanges = [
            'attributes' => [
                'name' => 'name',
                'text' => 'text',
                'snake_user.name' => 'another name',
            ],
            'old' => [
                'name' => 'name',
                'text' => 'text',
                'snake_user.name' => 'a name',
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_store_the_changes_when_updating_a_camel_case_related_model()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logOnly(['name', 'text', 'camel_user.name']);
            }

            public function camelUser()
            {
                return $this->belongsTo(User::class, 'user_id');
            }
        };

        $user = User::create([
            'name' => 'a name',
        ]);

        $anotherUser = User::create([
            'name' => 'another name',
        ]);

        $article = $articleClass::create([
            'name' => 'name',
            'text' => 'text',
            'user_id' => $user->id,
        ]);

        $article->user()->associate($anotherUser)->save();

        $expectedChanges = [
            'attributes' => [
                'name' => 'name',
                'text' => 'text',
                'camelUser.name' => 'another name',
            ],
            'old' => [
                'name' => 'name',
                'text' => 'text',
                'camelUser.name' => 'a name',
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_store_the_changes_when_updating_a_custom_case_related_model()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logOnly(['name', 'text', 'Custom_Case_User.name']);
            }

            public function Custom_Case_User()
            {
                return $this->belongsTo(User::class, 'user_id');
            }
        };

        $user = User::create([
            'name' => 'a name',
        ]);

        $anotherUser = User::create([
            'name' => 'another name',
        ]);

        $article = $articleClass::create([
            'name' => 'name',
            'text' => 'text',
            'user_id' => $user->id,
        ]);

        $article->user()->associate($anotherUser)->save();

        $expectedChanges = [
            'attributes' => [
                'name' => 'name',
                'text' => 'text',
                'Custom_Case_User.name' => 'another name',
            ],
            'old' => [
                'name' => 'name',
                'text' => 'text',
                'Custom_Case_User.name' => 'a name',
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_store_the_dirty_changes_when_updating_a_related_model()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logOnly(['name', 'text', 'user.name'])
                ->logOnlyDirty();
            }
        };

        $user = User::create([
            'name' => 'a name',
        ]);

        $anotherUser = User::create([
            'name' => 'another name',
        ]);

        $article = $articleClass::create([
            'name' => 'name',
            'text' => 'text',
            'user_id' => $user->id,
        ]);

        $article->user()->associate($anotherUser)->save();

        $expectedChanges = [
            'attributes' => [
                'user.name' => 'another name',
            ],
            'old' => [
                'user.name' => 'a name',
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_store_the_changes_when_saving_including_multi_level_related_model()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logOnly(['name', 'text', 'user.latest_article.name'])
                ->logOnlyDirty();
            }
        };

        $user = User::create([
            'name' => 'a name',
        ]);

        $articleClass::create([
            'name' => 'name #1',
            'text' => 'text #1',
            'user_id' => $user->id,
        ]);

        $articleClass::create([
            'name' => 'name #2',
            'text' => 'text #2',
            'user_id' => $user->id,
        ]);

        $expectedChanges = [
            'attributes' => [
                'name' => 'name #2',
                'text' => 'text #2',
                'user.latestArticle.name' => 'name #1',
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_will_store_no_changes_when_not_logging_attributes()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logOnly([]);
            }
        };

        $article = new $articleClass();

        $article->name = 'updated name';

        $article->save();

        $this->assertEquals(collect(), $this->getLastActivity()->changes());
    }

    /** @test */
    public function it_will_store_the_values_when_deleting_the_model()
    {
        $article = $this->createArticle();

        $article->delete();

        $expectedChanges = collect([
            'old' => [
                'name' => 'my name',
                'text' => null,
            ],
        ]);

        $this->assertEquals('deleted', $this->getLastActivity()->description);
        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes());
    }

    /** @test */
    public function it_will_store_the_values_when_deleting_the_model_with_softdeletes()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;
            use SoftDeletes;

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logOnly(['name', 'text']);
            }
        };

        $article = new $articleClass();
        $article->name = 'my name';
        $article->save();

        $article->delete();

        $expectedChanges = collect([
            'old' => [
                'name' => 'my name',
                'text' => null,
            ],
        ]);

        $this->assertEquals('deleted', $this->getLastActivity()->description);
        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes());

        $article->forceDelete();

        $expectedChanges = collect([
            'old' => [
                'name' => 'my name',
                'text' => null,
            ],
        ]);

        $activities = $article->activities;

        $this->assertCount(3, $activities);
        $this->assertEquals('deleted', $this->getLastActivity()->description);
        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes());
    }

    /** @test */
    public function it_can_store_the_changes_of_collection_casted_properties()
    {
        $articleClass = new class() extends Article {
            protected $casts = ['json' => 'collection'];

            use LogsActivity;

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logOnly(['json'])
                ->logOnlyDirty();
            }
        };

        $article = $articleClass::create([
            'json' => ['value' => 'original'],
        ]);

        $article->json = collect(['value' => 'updated']);
        $article->save();

        $expectedChanges = [
            'attributes' => [
                'json' => [
                    'value' => 'updated',
                ],
            ],
            'old' => [
                'json' => [
                    'value' => 'original',
                ],
            ],
        ];
        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_store_the_changes_of_array_casted_properties()
    {
        $articleClass = new class() extends Article {
            protected $casts = ['json' => 'array'];

            use LogsActivity;

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logOnly(['json'])
                ->logOnlyDirty();
            }
        };

        $article = $articleClass::create([
            'json' => ['value' => 'original'],
        ]);

        $article->json = collect(['value' => 'updated']);
        $article->save();

        $expectedChanges = [
            'attributes' => [
                'json' => [
                    'value' => 'updated',
                ],
            ],
            'old' => [
                'json' => [
                    'value' => 'original',
                ],
            ],
        ];
        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_store_the_changes_of_json_casted_properties()
    {
        $articleClass = new class() extends Article {
            protected $casts = ['json' => 'json'];

            use LogsActivity;

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logOnly(['json'])
                ->logOnlyDirty();
            }
        };

        $article = $articleClass::create([
            'json' => ['value' => 'original'],
        ]);

        $article->json = collect(['value' => 'updated']);
        $article->save();

        $expectedChanges = [
            'attributes' => [
                'json' => [
                    'value' => 'updated',
                ],
            ],
            'old' => [
                'json' => [
                    'value' => 'original',
                ],
            ],
        ];
        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_use_nothing_as_loggable_attributes()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            protected $fillable = ['name', 'text'];

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->dontLogFillable();
            }
        };

        $article = new $articleClass();
        $article->name = 'my name';
        $article->text = 'my text';
        $article->save();

        $expectedChanges = [];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_use_text_as_loggable_attributes()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            protected $fillable = ['name', 'text'];

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logOnly(['text'])
                ->dontLogFillable();
            }
        };

        $article = new $articleClass();
        $article->name = 'my name';
        $article->text = 'my text';
        $article->save();

        $expectedChanges = [
            'attributes' => [
                'text' => 'my text',
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_use_fillable_as_loggable_attributes()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            protected $fillable = ['name', 'text'];

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logFillable();
            }
        };

        $article = new $articleClass();
        $article->name = 'my name';
        $article->save();

        $expectedChanges = [
            'attributes' => [
                'name' => 'my name',
                'text' => null,
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_use_both_fillable_and_log_attributes()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            protected $fillable = ['name'];

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logOnly(['text'])
                ->logFillable();
            }
        };

        $article = new $articleClass();
        $article->name = 'my name';
        $article->text = 'my text';
        $article->save();

        $expectedChanges = [
            'attributes' => [
                'name' => 'my name',
                'text' => 'my text',
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_use_wildcard_for_loggable_attributes()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logAll();
            }
        };

        $article = new $articleClass();
        $article->name = 'my name';

        Carbon::setTestNow(Carbon::create(2017, 1, 1, 12, 0, 0));
        $article->save();

        $expectedChanges = [
            'attributes' => [
                'name' => 'my name',
                'text' => null,
                'deleted_at' => null,
                'id' => $article->id,
                'user_id' => null,
                'json' => null,
                'price' => null,
                'interval' => null,
                'created_at' => $this->isLaravel6OrLower() ? '2017-01-01 12:00:00' : '2017-01-01T12:00:00.000000Z',
                'updated_at' => $this->isLaravel6OrLower() ? '2017-01-01 12:00:00' : '2017-01-01T12:00:00.000000Z',
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_use_wildcard_with_relation()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logOnly(['*', 'user.name']);
            }
        };

        $user = User::create([
            'name' => 'user name',
        ]);

        Carbon::setTestNow(Carbon::create(2017, 1, 1, 12, 0, 0));

        $article = $articleClass::create([
            'name' => 'article name',
            'text' => 'article text',
            'user_id' => $user->id,
        ]);

        $expectedChanges = [
            'attributes' => [
                'id' => $article->id,
                'name' => 'article name',
                'text' => 'article text',
                'deleted_at' => null,
                'user_id' => $user->id,
                'json' => null,
                'price' => null,
                'created_at' => $this->isLaravel6OrLower() ? '2017-01-01 12:00:00' : '2017-01-01T12:00:00.000000Z',
                'updated_at' => $this->isLaravel6OrLower() ? '2017-01-01 12:00:00' : '2017-01-01T12:00:00.000000Z',
                'user.name' => 'user name',
                'interval' => null,
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_use_wildcard_when_updating_model()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logAll()
                ->logOnlyDirty();
            }
        };

        $user = User::create([
            'name' => 'user name',
        ]);

        Carbon::setTestNow(Carbon::create(2017, 1, 1, 12, 0, 0));
        $article = $articleClass::create([
            'name' => 'article name',
            'text' => 'article text',
            'user_id' => $user->id,
        ]);

        $article->name = 'changed name';
        Carbon::setTestNow(Carbon::create(2018, 1, 1, 12, 0, 0));
        $article->save();

        $expectedChanges = [
            'attributes' => [
                'name' => 'changed name',
                'updated_at' => $this->isLaravel6OrLower() ? '2018-01-01 12:00:00' : '2018-01-01T12:00:00.000000Z',
            ],
            'old' => [
                'name' => 'article name',
                'updated_at' => $this->isLaravel6OrLower() ? '2017-01-01 12:00:00' : '2017-01-01T12:00:00.000000Z',
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_store_the_changes_when_a_boolean_field_is_changed_from_false_to_null()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            protected $casts = [
                'text' => 'boolean',
            ];

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logAll()
                ->logOnlyDirty();
            }
        };

        $user = User::create([
            'name' => 'user name',
        ]);

        Carbon::setTestNow(Carbon::create(2017, 1, 1, 12, 0, 0));
        $article = $articleClass::create([
            'name' => 'article name',
            'text' => false,
            'user_id' => $user->id,
        ]);

        $article->text = null;
        Carbon::setTestNow(Carbon::create(2018, 1, 1, 12, 0, 0));
        $article->save();

        $expectedChanges = [
            'attributes' => [
                'text' => null,
                'updated_at' => $this->isLaravel6OrLower() ? '2018-01-01 12:00:00' : '2018-01-01T12:00:00.000000Z',

            ],
            'old' => [
                'text' => false,
                'updated_at' => $this->isLaravel6OrLower() ? '2017-01-01 12:00:00' : '2017-01-01T12:00:00.000000Z',
            ],
        ];

        $this->assertSame($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_use_ignored_attributes_while_updating()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logAll()
                ->logExcept(['name', 'updated_at']);
            }
        };

        $article = new $articleClass();
        $article->name = 'my name';

        Carbon::setTestNow(Carbon::create(2017, 1, 1, 12, 0, 0));
        $article->save();

        $expectedChanges = [
            'attributes' => [
                'text' => null,
                'deleted_at' => null,
                'id' => $article->id,
                'user_id' => null,
                'json' => null,
                'price' => null,
                'interval' => null,
                'created_at' => $this->isLaravel6OrLower() ? '2017-01-01 12:00:00' : '2017-01-01T12:00:00.000000Z',
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_use_unguarded_as_loggable_attributes()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            protected $guarded = ['text', 'json'];

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logUnguarded()
                ->logExcept(['id', 'created_at', 'updated_at', 'deleted_at']);
            }
        };

        $article = new $articleClass();
        $article->name = 'my name';
        $article->text = 'my new text';
        $article->save();

        $expectedChanges = [
            'attributes' => [
                'name' => 'my name',
                'user_id' => null,
                'price' => null,
                'interval' => null,
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_will_store_no_changes_when_wildcard_guard_and_log_unguarded_attributes()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            protected $guarded = ['*'];

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logUnguarded();
            }
        };

        $article = new $articleClass();
        $article->name = 'my name';
        $article->text = 'my new text';
        $article->save();

        $this->assertEquals([], $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_use_hidden_as_loggable_attributes()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            protected $hidden = ['text'];
            protected $fillable = ['name', 'text'];

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logOnly(['name', 'text']);
            }
        };

        $article = new $articleClass();
        $article->name = 'my name';
        $article->text = 'my text';
        $article->save();

        $expectedChanges = [
            'attributes' => [
                'name' => 'my name',
                'text' => 'my text',
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_use_overloaded_as_loggable_attributes()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            protected $fillable = ['name', 'text'];

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logOnly(['name', 'text', 'description']);
            }

            public function setDescriptionAttribute($value)
            {
                $this->attributes['json'] = json_encode(['description' => $value]);
            }

            public function getDescriptionAttribute()
            {
                return Arr::get(json_decode($this->attributes['json'], true), 'description');
            }
        };

        $article = new $articleClass();
        $article->name = 'my name';
        $article->text = 'my text';
        $article->description = 'my description';
        $article->save();

        $expectedChanges = [
            'attributes' => [
                'name' => 'my name',
                'text' => 'my text',
                'description' => 'my description',
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_use_mutated_as_loggable_attributes()
    {
        $userClass = new class() extends User {
            use LogsActivity;

            protected $fillable = ['name', 'text'];

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logAll();
            }

            public function setNameAttribute($value)
            {
                $this->attributes['name'] = strtoupper($value);
            }
        };

        Carbon::setTestNow(Carbon::create(2017, 1, 1, 12, 0, 0));
        $user = new $userClass();
        $user->name = 'my name';
        $user->text = 'my text';
        $user->save();

        $expectedChanges = [
            'attributes' => [
                'id' => $user->id,
                'name' => 'MY NAME',
                'text' => 'my text',
                'created_at' => $this->isLaravel6OrLower() ? '2017-01-01 12:00:00' : '2017-01-01T12:00:00.000000Z',
                'updated_at' => $this->isLaravel6OrLower() ? '2017-01-01 12:00:00' : '2017-01-01T12:00:00.000000Z',
                'deleted_at' => null,
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());

        $user->name = 'my name 2';
        $user->save();

        $expectedChanges = [
            'old' => [
                'id' => $user->id,
                'name' => 'MY NAME',
                'text' => 'my text',
                'created_at' => $this->isLaravel6OrLower() ? '2017-01-01 12:00:00' : '2017-01-01T12:00:00.000000Z',
                'updated_at' => $this->isLaravel6OrLower() ? '2017-01-01 12:00:00' : '2017-01-01T12:00:00.000000Z',
                'deleted_at' => null,
            ],
            'attributes' => [
                'id' => $user->id,
                'name' => 'MY NAME 2',
                'text' => 'my text',
                'created_at' => $this->isLaravel6OrLower() ? '2017-01-01 12:00:00' : '2017-01-01T12:00:00.000000Z',
                'updated_at' => $this->isLaravel6OrLower() ? '2017-01-01 12:00:00' : '2017-01-01T12:00:00.000000Z',
                'deleted_at' => null,
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_use_accessor_as_loggable_attributes()
    {
        $userClass = new class() extends User {
            use LogsActivity;

            protected $fillable = ['name', 'text'];

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logAll();
            }

            public function getNameAttribute($value)
            {
                return strtoupper($value);
            }
        };

        Carbon::setTestNow(Carbon::create(2017, 1, 1, 12, 0, 0));
        $user = new $userClass();
        $user->name = 'my name';
        $user->text = 'my text';
        $user->save();

        $expectedChanges = [
            'attributes' => [
                'id' => $user->id,
                'name' => 'MY NAME',
                'text' => 'my text',
                'created_at' => $this->isLaravel6OrLower() ? '2017-01-01 12:00:00' : '2017-01-01T12:00:00.000000Z',
                'updated_at' => $this->isLaravel6OrLower() ? '2017-01-01 12:00:00' : '2017-01-01T12:00:00.000000Z',
                'deleted_at' => null,
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());

        $user->name = 'my name 2';
        $user->save();

        $expectedChanges = [
            'old' => [
                'id' => $user->id,
                'name' => 'MY NAME',
                'text' => 'my text',
                'created_at' => $this->isLaravel6OrLower() ? '2017-01-01 12:00:00' : '2017-01-01T12:00:00.000000Z',
                'updated_at' => $this->isLaravel6OrLower() ? '2017-01-01 12:00:00' : '2017-01-01T12:00:00.000000Z',
                'deleted_at' => null,
            ],
            'attributes' => [
                'id' => $user->id,
                'name' => 'MY NAME 2',
                'text' => 'my text',
                'created_at' => $this->isLaravel6OrLower() ? '2017-01-01 12:00:00' : '2017-01-01T12:00:00.000000Z',
                'updated_at' => $this->isLaravel6OrLower() ? '2017-01-01 12:00:00' : '2017-01-01T12:00:00.000000Z',
                'deleted_at' => null,
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_use_encrypted_as_loggable_attributes()
    {
        $userClass = new class() extends User {
            use LogsActivity;

            protected $fillable = ['name', 'text'];
            protected $encryptable = ['name', 'text'];

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logOnly(['name', 'text']);
            }

            public function getAttributeValue($key)
            {
                $value = parent::getAttributeValue($key);

                if (in_array($key, $this->encryptable)) {
                    $value = decrypt($value);
                }

                return $value;
            }

            public function setAttribute($key, $value)
            {
                if (in_array($key, $this->encryptable)) {
                    $value = encrypt($value);
                }

                return parent::setAttribute($key, $value);
            }
        };

        Carbon::setTestNow(Carbon::create(2017, 1, 1, 12, 0, 0));
        $user = new $userClass();
        $user->name = 'my name';
        $user->text = 'my text';
        $user->save();

        $expectedChanges = [
            'attributes' => [
                'name' => 'my name',
                'text' => 'my text',
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());

        $user->name = 'my name 2';
        $user->save();

        $expectedChanges = [
            'old' => [
                'name' => 'my name',
                'text' => 'my text',
            ],
            'attributes' => [
                'name' => 'my name 2',
                'text' => 'my text',
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_use_casted_as_loggable_attribute()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            protected $casts = [
                'price' => 'float',
            ];

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logOnly(['name', 'text', 'price'])
                ->logOnlyDirty();
            }
        };

        $article = new $articleClass();
        $article->name = 'my name';
        $article->text = 'my text';
        $article->price = '9.99';
        $article->save();

        $expectedChanges = [
            'attributes' => [
                'name' => 'my name',
                'text' => 'my text',
                'price' => 9.99,
            ],
        ];

        $changes = $this->getLastActivity()->changes()->toArray();
        $this->assertSame($expectedChanges, $changes);
        $this->assertIsFloat($changes['attributes']['price']);

        $article->price = 19.99;
        $article->save();

        $expectedChanges = [
            'attributes' => [
                'price' => 19.99,
            ],
            'old' => [
                'price' => 9.99,
            ],
        ];

        $changes = $this->getLastActivity()->changes()->toArray();
        $this->assertSame($expectedChanges, $changes);
        $this->assertIsFloat($changes['attributes']['price']);
    }

    /** @test */
    public function it_can_use_nullable_date_as_loggable_attributes()
    {
        $userClass = new class() extends User {
            use LogsActivity;
            use SoftDeletes;

            protected $fillable = ['name', 'text'];

            protected $dates = [
                'created_at',
                'updated_at',
                'deleted_at',
            ];

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logAll();
            }
        };

        Carbon::setTestNow(Carbon::create(2017, 1, 1, 12, 0, 0));
        $user = new $userClass();
        $user->name = 'my name';
        $user->text = 'my text';
        $user->save();

        $expectedChanges = [
            'attributes' => [
                'id' => $user->getKey(),
                'name' => 'my name',
                'text' => 'my text',
                'created_at' => $this->isLaravel6OrLower() ? '2017-01-01 12:00:00' : '2017-01-01T12:00:00.000000Z',
                'updated_at' => $this->isLaravel6OrLower() ? '2017-01-01 12:00:00' : '2017-01-01T12:00:00.000000Z',
                'deleted_at' => null,
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_use_custom_date_cast_as_loggable_attributes()
    {
        $userClass = new class() extends User {
            use LogsActivity;

            protected $fillable = ['name', 'text'];
            protected $casts = [
                'created_at' => 'date:d.m.Y',
            ];

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logAll();
            }
        };

        Carbon::setTestNow(Carbon::create(2017, 1, 1, 12, 0, 0));
        $user = new $userClass();
        $user->name = 'my name';
        $user->text = 'my text';
        $user->save();

        $expectedChanges = [
            'attributes' => [
                'id' => $user->getKey(),
                'name' => 'my name',
                'text' => 'my text',
                'created_at' => '01.01.2017',
                'updated_at' => $this->isLaravel6OrLower() ? '2017-01-01 12:00:00' : '2017-01-01T12:00:00.000000Z',
                'deleted_at' => null,
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_store_the_changes_of_json_attributes()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            protected $casts = [
                'json' => 'collection',
            ];

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logOnly(['name', 'json->data'])
                ->logOnlyDirty();
            }
        };

        $article = new $articleClass();
        $article->json = ['data' => 'test'];
        $article->name = 'I am JSON';
        $article->save();

        $expectedChanges = [
            'attributes' => [
                'name' => 'I am JSON',
                'json' => [
                    'data' => 'test',
                ],
            ],
        ];

        $changes = $this->getLastActivity()->changes()->toArray();

        $this->assertSame($expectedChanges, $changes);
    }

    /** @test */
    public function it_will_not_store_changes_to_untracked_json()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            protected $casts = [
                'json' => 'collection',
            ];

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logOnly(['name', 'json->data'])
                ->logOnlyDirty();
            }
        };

        $article = new $articleClass();
        $article->json = ['unTracked' => 'test'];
        $article->name = 'a name';
        $article->save();

        $article->name = 'I am JSON';
        $article->json = ['unTracked' => 'different string'];
        $article->save();

        $expectedChanges = [
            'attributes' => [
                'name' => 'I am JSON',
            ],
            'old' => [
                'name' => 'a name',
            ],
        ];

        $changes = $this->getLastActivity()->changes()->toArray();

        $this->assertSame($expectedChanges, $changes);
    }

    /** @test */
    public function it_will_return_null_for_missing_json_attribute()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            protected $casts = [
                'json' => 'collection',
            ];

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logOnly(['name', 'json->data->missing'])
                ->logOnlyDirty();
            }
        };

        $jsonToStore = [];

        $article = new $articleClass();
        $article->json = $jsonToStore;
        $article->name = 'I am JSON';
        $article->save();

        data_set($jsonToStore, 'data.missing', 'I wasn\'t here');

        $article->json = $jsonToStore;
        $article->save();

        $expectedChanges = [
            'attributes' => [
                'json' => [
                    'data' => [
                        'missing' => 'I wasn\'t here',
                    ],
                ],
            ],
            'old' => [
                'json' => [
                    'data' => [
                        'missing' => null,
                    ],
                ],
            ],
        ];

        $changes = $this->getLastActivity()->changes()->toArray();

        $this->assertSame($expectedChanges, $changes);
    }

    /** @test */
    public function it_will_return_an_array_for_sub_key_in_json_attribute()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            protected $casts = [
                'json' => 'collection',
            ];

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logOnly(['name', 'json->data'])
                ->logOnlyDirty();
            }
        };

        $jsonToStore = [
            'data' => [
                'data_a' => 1,
                'data_b' => 2,
                'data_c' => 3,
                'data_d' => 4,
                'data_e' => 5,
            ],
        ];

        $article = new $articleClass();
        $article->json = $jsonToStore;
        $article->name = 'I am JSON';
        $article->save();

        data_set($jsonToStore, 'data.data_c', 'I Got The Key');

        $article->json = $jsonToStore;
        $article->save();

        $expectedChanges = [
            'attributes' => [
                'json' => [
                    'data' => [
                        'data_a' => 1,
                        'data_b' => 2,
                        'data_c' => 'I Got The Key',
                        'data_d' => 4,
                        'data_e' => 5,
                    ],
                ],
            ],
            'old' => [
                'json' => [
                    'data' => [
                        'data_a' => 1,
                        'data_b' => 2,
                        'data_c' => 3,
                        'data_d' => 4,
                        'data_e' => 5,
                    ],
                ],
            ],
        ];

        $changes = $this->getLastActivity()->changes()->toArray();

        $this->assertSame($expectedChanges, $changes);
    }

    /** @test */
    public function it_will_access_further_than_level_one_json_attribute()
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            protected $casts = [
                'json' => 'collection',
            ];

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logOnly(['name', 'json->data->can->go->how->far'])
                ->logOnlyDirty();
            }
        };

        $jsonToStore = [];
        // data_set($jsonToStore, 'data.can.go.how.far', 'Data');

        $article = new $articleClass();
        $article->json = $jsonToStore;
        $article->name = 'I am JSON';
        $article->save();

        data_set($jsonToStore, 'data.can.go.how.far', 'This far');

        $article->json = $jsonToStore;
        $article->save();

        $expectedChanges = [
            'attributes' => [
                'json' => [
                    'data' => [
                        'can' => [
                            'go' => [
                                'how' => [
                                    'far' => 'This far',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'old' => [
                'json' => [
                    'data' => [
                        'can' => [
                            'go' => [
                                'how' => [
                                    'far' => null,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $changes = $this->getLastActivity()->changes()->toArray();

        $this->assertSame($expectedChanges, $changes);
    }

    protected function createArticle(): Article
    {
        $article = new $this->article();
        $article->name = 'my name';
        $article->save();

        return $article;
    }

    protected function createDirtyArticle(): Article
    {
        $articleClass = new class() extends Article {
            use LogsActivity;

            public function getActivitylogOptions(): LogOptions
            {
                return LogOptions::defaults()
                ->logOnly(['name', 'text'])
                ->logOnlyDirty();
            }
        };

        $article = new $articleClass();
        $article->name = 'my name';
        $article->save();

        return $article;
    }
}
