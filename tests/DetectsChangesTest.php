<?php

use Carbon\Carbon;
use Carbon\CarbonInterval;
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

beforeEach(function () {
    $this->article = new class() extends Article {
        use LogsActivity;

        public function getActivitylogOptions(): LogOptions
        {
            return LogOptions::defaults()
            ->logOnly(['name', 'text']);
        }
    };

    $this->assertCount(0, Activity::all());
});

it('can store the values when creating a model', function () {
    $this->createArticle();

    $expectedChanges = [
        'attributes' => [
            'name' => 'my name',
            'text' => null,
        ],
    ];

    $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
});

it('deep diff check json field', function () {
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
});

it('detect changes for date inteval attributes', function () {
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
});

it('detect changes for null date inteval attributes', function () {
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
});

it('can store the relation values when creating a model', function () {
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
});

it('retruns same uuid for all log changes under one batch', function () {
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
});

it('assigns new uuid for multiple change logs in different batches', function () {
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
});

it('can removes key event if it was loggable', function () {
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
});

it('can store empty relation when creating a model', function () {
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
});

it('can store the changes when updating a model', function () {
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
});

it('can store dirty changes only', function () {
    $article = createDirtyArticle();

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
});

it('can store dirty changes for swapping values', function () {
    $article = createDirtyArticle();

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
});

it('can store the changes when updating a related model', function () {
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
});

it('can store the changes when updating a snake case related model', function () {
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
});

it('can store the changes when updating a camel case related model', function () {
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
});

it('can store the changes when updating a custom case related model', function () {
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
});

it('can store the dirty changes when updating a related model', function () {
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
});

it('can store the changes when saving including multi level related model', function () {
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
});

it('will store no changes when not logging attributes', function () {
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
});

it('will store the values when deleting the model', function () {
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
});

it('will store the values when deleting the model with softdeletes', function () {
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
    $this->assertEquals('forceDeleted', $this->getLastActivity()->description);
    $this->assertEquals($expectedChanges, $this->getLastActivity()->changes());
});

it('can store the changes of collection casted properties', function () {
    $articleClass = new class() extends Article {
        use LogsActivity;
        protected $casts = ['json' => 'collection'];

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
});

it('can store the changes of array casted properties', function () {
    $articleClass = new class() extends Article {
        use LogsActivity;
        protected $casts = ['json' => 'array'];

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
});

it('can store the changes of json casted properties', function () {
    $articleClass = new class() extends Article {
        use LogsActivity;
        protected $casts = ['json' => 'json'];

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
});

it('can use nothing as loggable attributes', function () {
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
});

it('can use text as loggable attributes', function () {
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
});

it('can use fillable as loggable attributes', function () {
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
});

it('can use both fillable and log attributes', function () {
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
});

it('can use wildcard for loggable attributes', function () {
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
            'status' => null,
            'created_at' =>  '2017-01-01T12:00:00.000000Z',
            'updated_at' =>  '2017-01-01T12:00:00.000000Z',
        ],
    ];

    $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
});

it('can use wildcard with relation', function () {
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
            'created_at' =>  '2017-01-01T12:00:00.000000Z',
            'updated_at' =>  '2017-01-01T12:00:00.000000Z',
            'user.name' => 'user name',
            'interval' => null,
            'status' => null,
        ],
    ];

    $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
});

it('can use wildcard when updating model', function () {
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
            'updated_at' => '2018-01-01T12:00:00.000000Z',
        ],
        'old' => [
            'name' => 'article name',
            'updated_at' =>  '2017-01-01T12:00:00.000000Z',
        ],
    ];

    $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
});

it('can store the changes when a boolean field is changed from false to null', function () {
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
            'updated_at' => '2018-01-01T12:00:00.000000Z',

        ],
        'old' => [
            'text' => false,
            'updated_at' =>  '2017-01-01T12:00:00.000000Z',
        ],
    ];

    $this->assertSame($expectedChanges, $this->getLastActivity()->changes()->toArray());
});

it('can use ignored attributes while updating', function () {
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
            'status' => null,
            'created_at' =>  '2017-01-01T12:00:00.000000Z',
        ],
    ];

    $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
});

it('can use unguarded as loggable attributes', function () {
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
            'status' => null,
        ],
    ];

    $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
});

it('will store no changes when wildcard guard and log unguarded attributes', function () {
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
});

it('can use hidden as loggable attributes', function () {
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
});

it('can use overloaded as loggable attributes', function () {
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
});

it('can use mutated as loggable attributes', function () {
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
            'created_at' =>  '2017-01-01T12:00:00.000000Z',
            'updated_at' =>  '2017-01-01T12:00:00.000000Z',
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
            'created_at' =>  '2017-01-01T12:00:00.000000Z',
            'updated_at' =>  '2017-01-01T12:00:00.000000Z',
            'deleted_at' => null,
        ],
        'attributes' => [
            'id' => $user->id,
            'name' => 'MY NAME 2',
            'text' => 'my text',
            'created_at' =>  '2017-01-01T12:00:00.000000Z',
            'updated_at' =>  '2017-01-01T12:00:00.000000Z',
            'deleted_at' => null,
        ],
    ];

    $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
});

it('can use accessor as loggable attributes', function () {
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
            'created_at' =>  '2017-01-01T12:00:00.000000Z',
            'updated_at' =>  '2017-01-01T12:00:00.000000Z',
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
            'created_at' =>  '2017-01-01T12:00:00.000000Z',
            'updated_at' =>  '2017-01-01T12:00:00.000000Z',
            'deleted_at' => null,
        ],
        'attributes' => [
            'id' => $user->id,
            'name' => 'MY NAME 2',
            'text' => 'my text',
            'created_at' =>  '2017-01-01T12:00:00.000000Z',
            'updated_at' =>  '2017-01-01T12:00:00.000000Z',
            'deleted_at' => null,
        ],
    ];

    $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
});

it('can use encrypted as loggable attributes', function () {
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
});

it('can use casted as loggable attribute', function () {
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
});

it('can use nullable date as loggable attributes', function () {
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
            'created_at' =>  '2017-01-01T12:00:00.000000Z',
            'updated_at' =>  '2017-01-01T12:00:00.000000Z',
            'deleted_at' => null,
        ],
    ];

    $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
});

it('can use custom date cast as loggable attributes', function () {
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
            'updated_at' =>  '2017-01-01T12:00:00.000000Z',
            'deleted_at' => null,
        ],
    ];

    $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
});

it('can use custom immutable date cast as loggable attributes', function () {
    $userClass = new class() extends User {
        use LogsActivity;

        protected $fillable = ['name', 'text'];
        protected $casts = [
            'created_at' => 'immutable_date:d.m.Y',
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
            'updated_at' =>  '2017-01-01T12:00:00.000000Z',
            'deleted_at' => null,
        ],
    ];

    $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
});

it('can store the changes of json attributes', function () {
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
});

it('will not store changes to untracked json', function () {
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
});

it('will return null for missing json attribute', function () {
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
});

it('will return an array for sub key in json attribute', function () {
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
});

it('will access further than level one json attribute', function () {
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
});

function createDirtyArticle(): Article
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

function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
    ->logOnly(['name', 'text'])
    ->logOnlyDirty();
}
