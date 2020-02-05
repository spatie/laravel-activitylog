<?php

namespace Spatie\Activitylog\Test;

use Illuminate\Database\Eloquent\Relations\Relation;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Test\Models\Article;
use Spatie\Activitylog\Test\Models\User;

class ActivityModelTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        collect(range(1, 5))->each(function (int $index) {
            $logName = "log{$index}";
            activity($logName)->log('hello everybody');
        });
    }

    /** @test */
    public function it_provides_a_scope_to_get_activities_from_a_specific_log()
    {
        $activityInLog3 = Activity::inLog('log3')->get();

        $this->assertCount(1, $activityInLog3);

        $this->assertEquals('log3', $activityInLog3->first()->log_name);
    }

    /** @test */
    public function it_provides_a_scope_to_get_log_items_from_multiple_logs()
    {
        $activity = Activity::inLog('log2', 'log4')->get();

        $this->assertCount(2, $activity);

        $this->assertEquals('log2', $activity->first()->log_name);
        $this->assertEquals('log4', $activity->last()->log_name);
    }

    /** @test */
    public function it_provides_a_scope_to_get_log_items_from_multiple_logs_using_an_array()
    {
        $activity = Activity::inLog(['log1', 'log2'])->get();

        $this->assertCount(2, $activity);

        $this->assertEquals('log1', $activity->first()->log_name);
        $this->assertEquals('log2', $activity->last()->log_name);
    }

    /** @test */
    public function it_provides_a_scope_to_get_log_items_for_a_specific_causer()
    {
        $subject = Article::first();
        $causer = User::first();

        activity()->on($subject)->by($causer)->log('Foo');
        activity()->on($subject)->by(User::create([
            'name' => 'Another User',
        ]))->log('Bar');

        $activities = Activity::causedBy($causer)->get();

        $this->assertCount(1, $activities);
        $this->assertEquals($causer->getKey(), $activities->first()->causer_id);
        $this->assertEquals(get_class($causer), $activities->first()->causer_type);
        $this->assertEquals('Foo', $activities->first()->description);
    }

    /** @test */
    public function it_provides_a_scope_to_get_log_items_for_a_specific_subject()
    {
        $subject = Article::first();
        $causer = User::first();

        activity()->on($subject)->by($causer)->log('Foo');
        activity()->on(Article::create([
            'name' => 'Another article',
        ]))->by($causer)->log('Bar');

        $activities = Activity::forSubject($subject)->get();

        $this->assertCount(1, $activities);
        $this->assertEquals($subject->getKey(), $activities->first()->subject_id);
        $this->assertEquals(get_class($subject), $activities->first()->subject_type);
        $this->assertEquals('Foo', $activities->first()->description);
    }

    /** @test */
    public function it_provides_a_scope_to_get_log_items_for_a_specific_morphmapped_causer()
    {
        Relation::morphMap([
            'articles' => 'Spatie\Activitylog\Test\Models\Article',
            'users' => 'Spatie\Activitylog\Test\Models\User',
        ]);

        $subject = Article::first();
        $causer = User::first();

        activity()->on($subject)->by($causer)->log('Foo');
        activity()->on($subject)->by(User::create([
            'name' => 'Another User',
        ]))->log('Bar');

        $activities = Activity::causedBy($causer)->get();

        $this->assertCount(1, $activities);
        $this->assertEquals($causer->getKey(), $activities->first()->causer_id);
        $this->assertEquals('users', $activities->first()->causer_type);
        $this->assertEquals('Foo', $activities->first()->description);

        Relation::morphMap([], false);
    }

    /** @test */
    public function it_provides_a_scope_to_get_log_items_for_a_specific_morphmapped_subject()
    {
        Relation::morphMap([
            'articles' => 'Spatie\Activitylog\Test\Models\Article',
            'users' => 'Spatie\Activitylog\Test\Models\User',
        ]);

        $subject = Article::first();
        $causer = User::first();

        activity()->on($subject)->by($causer)->log('Foo');
        activity()->on(Article::create([
            'name' => 'Another article',
        ]))->by($causer)->log('Bar');

        $activities = Activity::forSubject($subject)->get();

        $this->assertCount(1, $activities);
        $this->assertEquals($subject->getKey(), $activities->first()->subject_id);
        $this->assertEquals('articles', $activities->first()->subject_type);
        $this->assertEquals('Foo', $activities->first()->description);

        Relation::morphMap([], false);
    }
}
