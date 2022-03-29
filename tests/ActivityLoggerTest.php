<?php

namespace Spatie\Activitylog\Test;

use Auth;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Exceptions\CouldNotLogActivity;
use Spatie\Activitylog\Facades\CauserResolver;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Test\Models\Article;
use Spatie\Activitylog\Test\Models\User;

class ActivityLoggerTest extends TestCase
{
    /** @var string */
    protected $activityDescription;

    public function setUp(): void
    {
        $this->activityDescription = 'My activity';

        parent::setUp();
    }

    /** @test */
    public function it_can_log_an_activity()
    {
        activity()->log($this->activityDescription);

        $this->assertEquals($this->activityDescription, $this->getLastActivity()->description);
    }

    /** @test */
    public function it_will_not_log_an_activity_when_the_log_is_not_enabled()
    {
        config(['activitylog.enabled' => false]);

        activity()->log($this->activityDescription);

        $this->assertNull($this->getLastActivity());
    }

 
    /** @test */
    public function it_will_log_activity_with_a_null_log_name()
    {
        config(['activitylog.default_log_name' => null]);

        activity()->log($this->activityDescription);

        $this->assertEquals($this->activityDescription, $this->getLastActivity()->description);
    }

    /** @test */
    public function it_will_log_an_activity_when_enabled_option_is_null()
    {
        config(['activitylog.enabled' => null]);

        activity()->log($this->activityDescription);

        $this->assertEquals($this->activityDescription, $this->getLastActivity()->description);
    }

    /** @test */
    public function it_will_log_to_the_default_log_by_default()
    {
        activity()->log($this->activityDescription);

        $this->assertEquals(config('activitylog.default_log_name'), $this->getLastActivity()->log_name);
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
    public function it_can_log_an_activity_with_a_causer_other_than_user_model()
    {
        $article = Article::first();

        activity()
            ->causedBy($article)
            ->log($this->activityDescription);

        $firstActivity = Activity::first();

        $this->assertEquals($article->id, $firstActivity->causer->id);
        $this->assertInstanceOf(Article::class, $firstActivity->causer);
    }

    /** @test */
    public function it_can_log_an_activity_with_a_causer_that_has_been_set_from_other_context()
    {
        $causer = Article::first();
        CauserResolver::setCauser($causer);

        $article = Article::first();

        activity()
               ->log($this->activityDescription);

        $firstActivity = Activity::first();

        $this->assertEquals($article->id, $firstActivity->causer->id);
        $this->assertInstanceOf(Article::class, $firstActivity->causer);
    }

    /** @test */
    public function it_can_log_an_activity_with_a_causer_when_there_is_no_web_guard()
    {
        config(['auth.guards.web' => null]);
        config(['auth.guards.foo' => ['driver' => 'session', 'provider' => 'users']]);
        config(['activitylog.default_auth_driver' => 'foo']);

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

    /** @test */
    public function it_will_throw_an_exception_if_it_cannot_translate_a_causer_id()
    {
        $this->expectException(CouldNotLogActivity::class);

        activity()->causedBy(999);
    }

    /** @test */
    public function it_will_use_the_logged_in_user_as_the_causer_by_default()
    {
        $userId = 1;

        Auth::login(User::find($userId));

        activity()->log('hello poetsvrouwman');

        $this->assertInstanceOf(User::class, $this->getLastActivity()->causer);
        $this->assertEquals($userId, $this->getLastActivity()->causer->id);
    }

    /** @test */
    public function it_can_log_activity_using_an_anonymous_causer()
    {
        activity()
            ->causedByAnonymous()
            ->log('hello poetsvrouwman');

        $this->assertNull($this->getLastActivity()->causer_id);
        $this->assertNull($this->getLastActivity()->causer_type);
    }

    /** @test */
    public function it_will_override_the_logged_in_user_as_the_causer_when_an_anonymous_causer_is_specified()
    {
        $userId = 1;

        Auth::login(User::find($userId));

        activity()
            ->byAnonymous()
            ->log('hello poetsvrouwman');

        $this->assertNull($this->getLastActivity()->causer_id);
        $this->assertNull($this->getLastActivity()->causer_type);
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
    public function it_can_log_an_activity_with_event()
    {
        $article = Article::create(['name' => 'article name']);
        activity()
            ->performedOn($article)
            ->event('create')
            ->log('test event');

        $this->assertEquals('create', $this->getLastActivity()->event);
    }

    /** @test */
    public function it_will_not_replace_non_placeholders()
    {
        $description = 'hello: :hello';

        activity()->log($description);

        $this->assertEquals($description, $this->getLastActivity()->description);
    }

    public function it_returns_an_instance_of_the_activity_after_logging()
    {
        $activityModel = activity()->log('test');

        $this->assertInstanceOf(Activity::class, $activityModel);
    }

    /** @test */
    public function it_returns_an_instance_of_the_activity_log_after_logging_when_using_a_custom_model()
    {
        $activityClass = new class() extends Activity {
        };

        $activityClassName = get_class($activityClass);

        $this->app['config']->set('activitylog.activity_model', $activityClassName);

        $activityModel = activity()->log('test');

        $this->assertInstanceOf($activityClassName, $activityModel);
    }

    /** @test */
    public function it_will_not_log_an_activity_when_the_log_is_manually_disabled()
    {
        activity()->disableLogging();

        activity()->log($this->activityDescription);

        $this->assertNull($this->getLastActivity());
    }

    /** @test */
    public function it_will_log_an_activity_when_the_log_is_manually_enabled()
    {
        config(['activitylog.enabled' => false]);

        activity()->enableLogging();

        activity()->log($this->activityDescription);

        $this->assertEquals($this->activityDescription, $this->getLastActivity()->description);
    }

    /** @test */
    public function it_accepts_null_parameter_for_caused_by()
    {
        activity()->causedBy(null)->log('nothing');

        $this->markTestAsPassed();
    }

    /** @test */
    public function it_can_log_activity_when_attributes_are_changed_with_tap()
    {
        $properties = [
            'property' => [
                'subProperty' => 'value',
            ],
        ];

        activity()
            ->tap(function (Activity $activity) use ($properties) {
                $activity->properties = collect($properties);
                $activity->created_at = Carbon::yesterday()->startOfDay();
            })
            ->log($this->activityDescription);

        $firstActivity = Activity::first();

        $this->assertInstanceOf(Collection::class, $firstActivity->properties);
        $this->assertEquals('value', $firstActivity->getExtraProperty('property.subProperty'));
        $this->assertEquals(Carbon::yesterday()->startOfDay()->format('Y-m-d H:i:s'), $firstActivity->created_at->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_will_log_a_custom_created_at_date_time()
    {
        $activityDateTime = now()->subDays(10);

        activity()
            ->createdAt($activityDateTime)
            ->log('created');

        $firstActivity = Activity::first();

        $this->assertEquals($activityDateTime->toAtomString(), $firstActivity->created_at->toAtomString());
    }

    /** @test */
    public function it_will_disable_logs_for_a_callback()
    {
        $result = activity()->withoutLogs(function () {
            activity()->log('created');

            return 'hello';
        });

        $this->assertNull($this->getLastActivity());
        $this->assertEquals('hello', $result);
    }

    /** @test */
    public function it_will_disable_logs_for_a_callback_without_affecting_previous_state()
    {
        activity()->withoutLogs(function () {
            activity()->log('created');
        });

        $this->assertNull($this->getLastActivity());

        activity()->log('outer');

        $this->assertEquals('outer', $this->getLastActivity()->description);
    }

    /** @test */
    public function it_will_disable_logs_for_a_callback_without_affecting_previous_state_even_when_already_disabled()
    {
        activity()->disableLogging();

        activity()->withoutLogs(function () {
            activity()->log('created');
        });

        $this->assertNull($this->getLastActivity());

        activity()->log('outer');

        $this->assertNull($this->getLastActivity());
    }

    /** @test */
    public function it_will_disable_logs_for_a_callback_without_affecting_previous_state_even_with_exception()
    {
        activity()->disableLogging();

        try {
            activity()->withoutLogs(function () {
                activity()->log('created');

                throw new Exception('OH NO');
            });
        } catch (Exception $ex) {
            //
        }

        $this->assertNull($this->getLastActivity());

        activity()->log('outer');

        $this->assertNull($this->getLastActivity());
    }
}
