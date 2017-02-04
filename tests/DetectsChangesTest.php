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
            static $logAttributes = ['name', 'text'];

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
            ],
        ];

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes->toArray());
    }

    /** @test */
    public function it_can_store_the_relation_values_when_creating_a_model()
    {
        $articleClass = new class() extends Article {
            static $logAttributes = ['name', 'text', 'user.name'];

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

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes->toArray());
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

        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes->toArray());
    }

    /** @test */
    public function it_will_store_no_changes_when_not_logging_attributes()
    {
        $articleClass = new class() extends Article {
            static $logAttributes = [];

            use LogsActivity;
        };

        $article = new $articleClass();

        $article->name = 'updated name';

        $article->save();

        $this->assertEquals(collect(), $this->getLastActivity()->changes);
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
        $this->assertEquals($expectedChanges, $this->getLastActivity()->changes);
    }

    protected function createArticle(): Article
    {
        $article = new $this->article();
        $article->name = 'my name';
        $article->save();

        return $article;
    }
}
