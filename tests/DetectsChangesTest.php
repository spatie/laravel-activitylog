<?php

namespace Spatie\Activitylog\Test;

use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Test\Models\User;
use Spatie\Activitylog\Test\Models\Article;
use Spatie\Activitylog\Traits\LogsActivity;

class DetectsChangesTest extends TestCase
{
    /** @var \Spatie\Activitylog\Test\Article|\Spatie\Activitylog\Traits\LogsActivity */
    protected $article;

    public function setUp()
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
            'old'        => [
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
            ],
        ]);

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
