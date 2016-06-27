<?php

namespace Spatie\Activitylog\Test;

use Auth;
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

        $this->assertEquals($this->activityDescription, $this->getLastActivity()->description);
        $this->assertEquals('', $this->getLastActivity()->log_name);
    }

    /** @test */
    public function it_can_log_an_activity_to_a_specific_log()
    {
        $customLogName = 'secondLog';

        activity($customLogName)->log($this->activityDescription);
        $this->assertEquals($customLogName, $this->getLastActivity()->log_name);

        activity()->useLog($customLogName)->log($this->activityDescription);
        $this->assertEquals($customLogName, $this->getLastActivity()->log_name);
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
    public function it_can_log_activity_with_properties()
    {
        $properties = [
            'property' => [
                'subProperty' => 'value',
            ],
        ];

        activity()
            ->withProperties($properties)
            ->log($this->activityDescription);

        $firstActivity = Activity::first();

        $this->assertInstanceOf(Collection::class, $firstActivity->properties);
        $this->assertEquals('value', $firstActivity->getExtraProperty('property.subProperty'));
    }

    /** @test */
    public function it_can_log_activity_with_a_single_properties()
    {
        activity()
            ->withProperty('key', 'value')
            ->log($this->activityDescription);

        $firstActivity = Activity::first();

        $this->assertInstanceOf(Collection::class, $firstActivity->properties);
        $this->assertEquals('value', $firstActivity->getExtraProperty('key'));
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

    /**
     * @test
     *
     * @requires !Travis
     */
    public function it_will_throw_an_exception_if_it_cannot_translate_a_causer_id()
    {
        $this->expectException(CouldNotLogActivity::class);

        activity()->causedBy(999);
    }

    /**
     * @test
     *
     * @requires !Travis
     */
    public function it_will_use_the_logged_in_user_as_the_causer_by_default()
    {
        $userId = 1;

        Auth::login(User::find($userId));

        activity()->log('hello poetsvrouwman');

        $this->assertInstanceOf(User::class, $this->getLastActivity()->causer);
        $this->assertEquals($userId, $this->getLastActivity()->causer->id);
    }

    /** @test */
    public function it_can_replace_the_placeholders()
    {
        $article = Article::create(['name' => 'article name']);

        $user = Article::create(['name' => 'user name']);

        activity()
            ->performedOn($article)
            ->causedBy($user)
            ->withProperties(['key' => 'value', 'key2' => ['subkey' => 'subvalue']])
            ->log('Subject name is :subject.name, causer name is :causer.name and property key is :properties.key and sub key :properties.key2.subkey');

        $expectedDescription = 'Subject name is article name, causer name is user name and property key is value and sub key subvalue';

        $this->assertEquals($expectedDescription, $this->getLastActivity()->description);
    }

    /** @test */
    public function it_will_not_replace_non_placeholders()
    {
        $description = 'hello: :hello';

        activity()->log($description);

        $this->assertEquals($description, $this->getLastActivity()->description);
    }
}
