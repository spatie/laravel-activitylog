<?php

namespace Spatie\Activitylog\Test;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Test\Models\User;
use Spatie\Activitylog\Test\Models\Article;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class DetectsChangesTest extends TestCase
{
    /** @var \Spatie\Activitylog\Test\Models\Article|\Spatie\Activitylog\Traits\LogsActivity */
    protected $article;

    public function setUp(): void
    {
        parent::setUp();

        $this->article = new class() extends Article {
            public static $logAttributes = ['name', 'text'];

            use LogsActivity;
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
    public function it_can_store_the_relation_values_when_creating_a_model()
    {
        $articleClass = new class() extends Article {
            public static $logAttributes = ['name', 'text', 'user.name'];

            use LogsActivity;
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
    public function it_can_store_empty_relation_when_creating_a_model()
    {
        $articleClass = new class() extends Article {
            public static $logAttributes = ['name', 'text', 'user.name'];

            use LogsActivity;
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
            public static $logAttributes = ['name', 'text', 'user.name'];

            use LogsActivity;
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
            public static $logAttributes = ['name', 'text', 'snake_user.name'];

            use LogsActivity;

            public function snakeUser()
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
                'snakeUser.name' => 'another name',
            ],
            'old' => [
                'name' => 'name',
                'text' => 'text',
                'snakeUser.name' => 'a name',
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_store_the_dirty_changes_when_updating_a_related_model()
    {
        $articleClass = new class() extends Article {
            public static $logAttributes = ['name', 'text', 'user.name'];

            public static $logOnlyDirty = true;

            use LogsActivity;
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
    public function it_will_store_no_changes_when_not_logging_attributes()
    {
        $articleClass = new class() extends Article {
            public static $logAttributes = [];

            use LogsActivity;
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
            'attributes' => [
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
            public static $logAttributes = ['name', 'text'];

            use LogsActivity, SoftDeletes;
        };

        $article = new $articleClass();
        $article->name = 'my name';
        $article->save();

        $article->delete();

        $expectedChanges = collect([
            'attributes' => [
                'name' => 'my name',
                'text' => null,
            ],
        ]);

        $this->assertEquals('deleted', $this->getLastActivity()->description);
        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes());

        $article->forceDelete();

        $expectedChanges = collect([
            'attributes' => [
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
    public function it_can_store_the_changes_of_array_casted_properties()
    {
        $articleClass = new class() extends Article {
            public static $logAttributes = ['json'];
            public static $logOnlyDirty = true;
            protected $casts = ['json' => 'collection'];

            use LogsActivity;
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
            protected $fillable = ['name', 'text'];
            protected static $logFillable = false;

            use LogsActivity;
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
            protected $fillable = ['name', 'text'];
            protected static $logAttributes = ['text'];
            protected static $logFillable = false;

            use LogsActivity;
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
            protected $fillable = ['name', 'text'];
            protected static $logFillable = true;

            use LogsActivity;
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
            protected $fillable = ['name'];
            protected static $logAttributes = ['text'];
            protected static $logFillable = true;

            use LogsActivity;
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
            public static $logAttributes = ['*'];

            use LogsActivity;
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
                'created_at' => '2017-01-01 12:00:00',
                'updated_at' => '2017-01-01 12:00:00',
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_use_wildcard_with_relation()
    {
        $articleClass = new class() extends Article {
            public static $logAttributes = ['*', 'user.name'];

            use LogsActivity;
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
                'created_at' => '2017-01-01 12:00:00',
                'updated_at' => '2017-01-01 12:00:00',
                'user.name' => 'user name',
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_use_wildcard_when_updating_model()
    {
        $articleClass = new class() extends Article {
            public static $logAttributes = ['*'];
            public static $logOnlyDirty = true;

            use LogsActivity;
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
                'updated_at' => '2018-01-01 12:00:00',
            ],
            'old' => [
                'name' => 'article name',
                'updated_at' => '2017-01-01 12:00:00',
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_store_the_changes_when_a_boolean_field_is_changed_from_null_to_false()
    {
        $articleClass = new class() extends Article {
            public static $logAttributes = ['*'];
            public static $logOnlyDirty = true;

            protected $casts = [
                'text' => 'boolean',
            ];

            use LogsActivity;
        };

        $user = User::create([
            'name' => 'user name',
        ]);

        Carbon::setTestNow(Carbon::create(2017, 1, 1, 12, 0, 0));
        $article = $articleClass::create([
            'name' => 'article name',
            'text' => null,
            'user_id' => $user->id,
        ]);

        $article->text = false;
        Carbon::setTestNow(Carbon::create(2018, 1, 1, 12, 0, 0));
        $article->save();

        $expectedChanges = [
            'attributes' => [
                'text' => false,
                'updated_at' => '2018-01-01 12:00:00',
            ],
            'old' => [
                'text' => null,
                'updated_at' => '2017-01-01 12:00:00',
            ],
        ];

        $this->assertSame($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_store_the_changes_when_a_boolean_field_is_changed_from_false_to_null()
    {
        $articleClass = new class() extends Article {
            public static $logAttributes = ['*'];
            public static $logOnlyDirty = true;

            protected $casts = [
                'text' => 'boolean',
            ];

            use LogsActivity;
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
                'updated_at' => '2018-01-01 12:00:00',
            ],
            'old' => [
                'text' => false,
                'updated_at' => '2017-01-01 12:00:00',
            ],
        ];

        $this->assertSame($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_use_ignored_attributes_while_updating()
    {
        $articleClass = new class() extends Article {
            public static $logAttributes = ['*'];
            public static $logAttributesToIgnore = ['name', 'updated_at'];

            use LogsActivity;
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
                'created_at' => '2017-01-01 12:00:00',
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_use_unguarded_as_loggable_attributes()
    {
        $articleClass = new class() extends Article {
            protected $guarded = ['text', 'json'];
            protected static $logAttributesToIgnore = ['id', 'created_at', 'updated_at', 'deleted_at'];
            protected static $logUnguarded = true;

            use LogsActivity;
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
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_will_store_no_changes_when_wildcard_guard_and_log_unguarded_attributes()
    {
        $articleClass = new class() extends Article {
            protected $guarded = ['*'];
            protected static $logUnguarded = true;

            use LogsActivity;
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
            protected $hidden = ['text'];
            protected $fillable = ['name', 'text'];
            protected static $logAttributes = ['name', 'text'];

            use LogsActivity;
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
            protected $fillable = ['name', 'text'];
            protected static $logAttributes = ['name', 'text', 'description'];

            use LogsActivity;

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
            protected $fillable = ['name', 'text'];
            protected static $logAttributes = ['*'];

            use LogsActivity;

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
                'created_at' => '2017-01-01 12:00:00',
                'updated_at' => '2017-01-01 12:00:00',
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
                'created_at' => '2017-01-01 12:00:00',
                'updated_at' => '2017-01-01 12:00:00',
                'deleted_at' => null,
            ],
            'attributes' => [
                'id' => $user->id,
                'name' => 'MY NAME 2',
                'text' => 'my text',
                'created_at' => '2017-01-01 12:00:00',
                'updated_at' => '2017-01-01 12:00:00',
                'deleted_at' => null,
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_use_accessor_as_loggable_attributes()
    {
        $userClass = new class() extends User {
            protected $fillable = ['name', 'text'];
            protected static $logAttributes = ['*'];

            use LogsActivity;

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
                'created_at' => '2017-01-01 12:00:00',
                'updated_at' => '2017-01-01 12:00:00',
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
                'created_at' => '2017-01-01 12:00:00',
                'updated_at' => '2017-01-01 12:00:00',
                'deleted_at' => null,
            ],
            'attributes' => [
                'id' => $user->id,
                'name' => 'MY NAME 2',
                'text' => 'my text',
                'created_at' => '2017-01-01 12:00:00',
                'updated_at' => '2017-01-01 12:00:00',
                'deleted_at' => null,
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_use_encrypted_as_loggable_attributes()
    {
        $userClass = new class() extends User {
            protected $fillable = ['name', 'text'];
            protected $encryptable = ['name', 'text'];
            protected static $logAttributes = ['name', 'text'];

            use LogsActivity;

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
            protected static $logAttributes = ['name', 'text', 'price'];
            public static $logOnlyDirty = true;
            protected $casts = [
                'price' => 'float',
            ];

            use LogsActivity;
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

    public function it_can_use_nullable_date_as_loggable_attributes()
    {
        $userClass = new class() extends User {
            protected $fillable = ['name', 'text'];
            protected static $logAttributes = ['*'];
            protected $dates = [
                'created_at',
                'updated_at',
                'deleted_at',
            ];

            use LogsActivity, SoftDeletes;
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
                'created_at' => '2017-01-01 12:00:00',
                'updated_at' => '2017-01-01 12:00:00',
                'deleted_at' => null,
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes()->toArray());
    }

    /** @test */
    public function it_can_store_the_changes_of_json_attributes()
    {
        $articleClass = new class() extends Article {
            protected static $logAttributes = ['name', 'json->data'];
            public static $logOnlyDirty = true;
            protected $casts = [
                'json' => 'collection',
            ];

            use LogsActivity;
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
            protected static $logAttributes = ['name', 'json->data'];
            public static $logOnlyDirty = true;
            protected $casts = [
                'json' => 'collection',
            ];

            use LogsActivity;
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
            protected static $logAttributes = ['name', 'json->data->missing'];
            public static $logOnlyDirty = true;
            protected $casts = [
                'json' => 'collection',
            ];

            use LogsActivity;
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
                'json' =>  [
                    'data' => [
                        'missing' => 'I wasn\'t here',
                    ],
                ],
            ],
            'old' => [
                'json' =>  [
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
            protected static $logAttributes = ['name', 'json->data'];
            public static $logOnlyDirty = true;
            protected $casts = [
                'json' => 'collection',
            ];

            use LogsActivity;
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
                'json' =>  [
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
                'json' =>  [
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
            protected static $logAttributes = ['name', 'json->data->can->go->how->far'];
            public static $logOnlyDirty = true;
            protected $casts = [
                'json' => 'collection',
            ];

            use LogsActivity;
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
                'json' =>  [
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
                'json' =>  [
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
            public static $logAttributes = ['name', 'text'];

            public static $logOnlyDirty = true;

            use LogsActivity;
        };

        $article = new $articleClass();
        $article->name = 'my name';
        $article->save();

        return $article;
    }
}
