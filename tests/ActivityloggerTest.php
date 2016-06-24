<?php

namespace Spatie\Activitylog\Test;

use Illuminate\Support\Collection;
use Spatie\Activitylog\Exceptions\CouldNotLogActivity;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Test\Models\Article;
use Spatie\Activitylog\Test\Models\User;

class ActivityloggerTest extends TestCase
{
    /** @var string */
    protected $activityDescription;

    public function setUp()
    {
        $this->activityDescription = 'My activity';

        parent::setUp();
    }

    /** @test */
    public function it_can_log_an_activity()
    {
        activity()->log($this->activityDescription);

        $this->assertEquals($this->activityDescription, Activity::first()->description);
    }

    /** @test */
    public function it_can_log_an_activity_with_a_subject()
    {
        $subject = Article::first();

        activity()
            ->performedOn($subject)
            ->log($this->activityDescription);

        $firstActivity = Activity::first();

        $this->assertEquals($subject->id, $firstActivity->subject->id);
        $this->assertInstanceOf(Article::class, $firstActivity->subject);
    }

    /** @test */
    public function it_can_log_an_activity_with_a_causer()
    {
        $user = User::first();

        activity()
            ->causedBy($user)
            ->log($this->activityDescription);

        $firstActivity = Activity::first();

        $this->assertEquals($user->id, $firstActivity->causer->id);
        $this->assertInstanceOf(User::class, $firstActivity->causer);
    }

    /** @test */
    public function it_can_log_activity_with_extra_properties()
    {
        $extraProperties = [
            'property' => [
                'subProperty' => 'value'
            ]
        ];

        activity()
            ->withExtraProperties($extraProperties)
            ->log($this->activityDescription);

        $firstActivity = Activity::first();

        $this->assertInstanceOf(Collection::class, $firstActivity->extra_properties);
        $this->assertEquals('value', $firstActivity->getExtraProperty('property.subProperty'));
    }

    /** @test */
    public function it_can_translate_a_given_causer_id_to_an_object()
    {
        $userId = User::first()->id;

        activity()
            ->causedBy($userId)
            ->log($this->activityDescription);

        $firstActivity = Activity::first();

        $this->assertInstanceOf(User::class, $firstActivity->causer);
        $this->assertEquals($userId, $firstActivity->causer->id);
    }

    /** @test */
    public function it_will_throw_an_exception_if_it_cannot_translate_a_causer_id()
    {
        $this->expectException(CouldNotLogActivity::class);
        
        activity()->causedBy(999);
    }
}
