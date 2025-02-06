<?php

use Carbon\Carbon;
use Illuminate\Database\Eloquent\JsonEncodingException;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Exceptions\CouldNotLogActivity;
use Spatie\Activitylog\Facades\Activity as ActivityFacade;
use Spatie\Activitylog\Facades\CauserResolver;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Test\Enums\NonBackedEnum;
use Spatie\Activitylog\Test\Models\Article;
use Spatie\Activitylog\Test\Models\User;
use Spatie\Activitylog\Traits\LogsActivity;

beforeEach(function () {
    $this->activityDescription = 'My activity';
});

it('can log an activity', function () {
    activity()->log($this->activityDescription);

    expect($this->getLastActivity()->description)->toEqual($this->activityDescription);
});

it('can log an activity with facade', function () {
    ActivityFacade::log($this->activityDescription);

    expect($this->getLastActivity()->description)->toEqual($this->activityDescription);
});

it('will not log an activity when the log is not enabled', function () {
    config(['activitylog.enabled' => false]);

    activity()->log($this->activityDescription);

    expect($this->getLastActivity())->toBeNull();
});

it('will log activity with a null log name', function () {
    config(['activitylog.default_log_name' => null]);

    activity()->log($this->activityDescription);

    expect($this->getLastActivity()->log_name)->toBeNull();
});

it('will log an activity when enabled option is null', function () {
    config(['activitylog.enabled' => null]);

    activity()->log($this->activityDescription);

    expect($this->getLastActivity()->description)->toEqual($this->activityDescription);
});

it('will log to the default log by default', function () {
    activity()->log($this->activityDescription);

    expect($this->getLastActivity()->log_name)->toEqual(config('activitylog.default_log_name'));
});

it('can log an activity to a specific log', function () {
    $customLogName = 'secondLog';

    activity($customLogName)->log($this->activityDescription);
    expect($this->getLastActivity()->log_name)->toEqual($customLogName);

    activity()->useLog($customLogName)->log($this->activityDescription);
    expect($this->getLastActivity()->log_name)->toEqual($customLogName);
});

it('can log an activity with a subject', function () {
    $subject = Article::first();

    activity()
        ->performedOn($subject)
        ->log($this->activityDescription);

    $firstActivity = Activity::first();

    expect($firstActivity->subject->id)->toEqual($subject->id);
    expect($firstActivity->subject)->toBeInstanceOf(Article::class);
});

it('can log an activity with a causer', function () {
    $user = User::first();

    activity()
        ->causedBy($user)
        ->log($this->activityDescription);

    $firstActivity = Activity::first();

    expect($firstActivity->causer->id)->toEqual($user->id);
    expect($firstActivity->causer)->toBeInstanceOf(User::class);
});

it('can log an activity with a causer other than user model', function () {
    $article = Article::first();

    activity()
        ->causedBy($article)
        ->log($this->activityDescription);

    $firstActivity = Activity::first();

    expect($firstActivity->causer->id)->toEqual($article->id);
    expect($firstActivity->causer)->toBeInstanceOf(Article::class);
});

it('can log an activity with a causer that has been set from other context', function () {
    $causer = Article::first();
    CauserResolver::setCauser($causer);

    $article = Article::first();

    activity()
           ->log($this->activityDescription);

    $firstActivity = Activity::first();

    expect($firstActivity->causer->id)->toEqual($article->id);
    expect($firstActivity->causer)->toBeInstanceOf(Article::class);
});

it('can log an activity with a causer when there is no web guard', function () {
    config(['auth.guards.web' => null]);
    config(['auth.guards.foo' => ['driver' => 'session', 'provider' => 'users']]);
    config(['activitylog.default_auth_driver' => 'foo']);

    $user = User::first();

    activity()
        ->causedBy($user)
        ->log($this->activityDescription);

    $firstActivity = Activity::first();

    expect($firstActivity->causer->id)->toEqual($user->id);
    expect($firstActivity->causer)->toBeInstanceOf(User::class);
});

it('can log activity with properties', function () {
    $properties = [
        'property' => [
            'subProperty' => 'value',
        ],
    ];

    activity()
        ->withProperties($properties)
        ->log($this->activityDescription);

    $firstActivity = Activity::first();

    expect($firstActivity->properties)->toBeInstanceOf(Collection::class);
    expect($firstActivity->getExtraProperty('property.subProperty'))->toEqual('value');
});

it('can log activity with null properties', function () {
    $properties = [
        'property' => null,
    ];

    activity()
        ->withProperties($properties)
        ->log($this->activityDescription);

    $firstActivity = Activity::first();

    expect($firstActivity->properties)->toBeInstanceOf(Collection::class);
    expect($firstActivity->getExtraProperty('property'))->toBeNull();
});

it('can log activity with a single properties', function () {
    activity()
        ->withProperty('key', 'value')
        ->log($this->activityDescription);

    $firstActivity = Activity::first();

    expect($firstActivity->properties)->toBeInstanceOf(Collection::class);
    expect($firstActivity->getExtraProperty('key'))->toEqual('value');
    expect($firstActivity->getExtraProperty('non_existant', 'default value'))->toEqual('default value');
});

it('can translate a given causer id to an object', function () {
    $userId = User::first()->id;

    activity()
        ->causedBy($userId)
        ->log($this->activityDescription);

    $firstActivity = Activity::first();

    expect($firstActivity->causer)->toBeInstanceOf(User::class);
    expect($firstActivity->causer->id)->toEqual($userId);
});

it('will throw an exception if it cannot translate a causer id', function () {
    $this->expectException(CouldNotLogActivity::class);

    activity()->causedBy(999);
});

it('will use the logged in user as the causer by default', function () {
    $userId = 1;

    Auth::login(User::find($userId));

    activity()->log('hello poetsvrouwman');

    expect($this->getLastActivity()->causer)->toBeInstanceOf(User::class);
    expect($this->getLastActivity()->causer->id)->toEqual($userId);
});

it('can log activity using an anonymous causer', function () {
    activity()
        ->causedByAnonymous()
        ->log('hello poetsvrouwman');

    expect($this->getLastActivity()->causer_id)->toBeNull();
    expect($this->getLastActivity()->causer_type)->toBeNull();
});

it('will override the logged in user as the causer when an anonymous causer is specified', function () {
    $userId = 1;

    Auth::login(User::find($userId));

    activity()
        ->byAnonymous()
        ->log('hello poetsvrouwman');

    expect($this->getLastActivity()->causer_id)->toBeNull();
    expect($this->getLastActivity()->causer_type)->toBeNull();
});

it('can replace the placeholders', function () {
    $article = Article::create(['name' => 'article name']);

    $user = Article::create(['name' => 'user name']);

    activity()
        ->performedOn($article)
        ->causedBy($user)
        ->withProperties(['key' => 'value', 'key2' => ['subkey' => 'subvalue']])
        ->log('Subject name is :subject.name, causer name is :causer.name and property key is :properties.key and sub key :properties.key2.subkey');

    $expectedDescription = 'Subject name is article name, causer name is user name and property key is value and sub key subvalue';

    expect($this->getLastActivity()->description)->toEqual($expectedDescription);
});

it('can replace the placeholders with object properties and accessors', function () {
    $article = Article::create([
        'name' => 'article name',
        'user_id' => User::first()->id,
    ]);

    $article->foo = new stdClass();
    $article->foo->bar = new stdClass();
    $article->foo->bar->baz = 'zal';

    activity()
        ->performedOn($article)
        ->withProperties(['key' => 'value', 'key2' => ['subkey' => 'subvalue']])
        ->log('Subject name is :subject.name, deeply nested property is :subject.foo.bar.baz, accessor property is :subject.owner_name');

    $expectedDescription = 'Subject name is article name, deeply nested property is zal, accessor property is name 1';

    expect($this->getLastActivity()->description)->toEqual($expectedDescription);
});

it('can log an activity with event', function () {
    $article = Article::create(['name' => 'article name']);
    activity()
        ->performedOn($article)
        ->event('create')
        ->log('test event');

    expect($this->getLastActivity()->event)->toEqual('create');
});

it('will not replace non placeholders', function () {
    $description = 'hello: :hello';

    activity()->log($description);

    expect($this->getLastActivity()->description)->toEqual($description);
});

it('returns an instance of the activity log after logging when using a custom model', function () {
    $activityClass = new class() extends Activity {
    };

    $activityClassName = get_class($activityClass);

    app()['config']->set('activitylog.activity_model', $activityClassName);

    $activityModel = activity()->log('test');

    expect($activityModel)->toBeInstanceOf($activityClassName);
});

it('will not log an activity when the log is manually disabled', function () {
    activity()->disableLogging();

    activity()->log($this->activityDescription);

    expect($this->getLastActivity())->toBeNull();
});

it('will log an activity when the log is manually enabled', function () {
    config(['activitylog.enabled' => false]);

    activity()->enableLogging();

    activity()->log($this->activityDescription);

    expect($this->getLastActivity()->description)->toEqual($this->activityDescription);
});

it('accepts null parameter for caused by', function () {
    activity()->causedBy(null)->log('nothing');

    $this->markTestAsPassed();
});

it('can log activity when attributes are changed with tap', function () {
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

    expect($firstActivity->properties)->toBeInstanceOf(Collection::class);
    expect($firstActivity->getExtraProperty('property.subProperty'))->toEqual('value');
    expect($firstActivity->created_at->format('Y-m-d H:i:s'))->toEqual(Carbon::yesterday()->startOfDay()->format('Y-m-d H:i:s'));
});

it('will tap a subject', function () {
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

    activity()
        ->on($model)
        ->log($this->activityDescription);

    $firstActivity = Activity::first();
    $this->assertEquals('my custom description', $firstActivity->description);
});

it('will log a custom created at date time', function () {
    $activityDateTime = now()->subDays(10);

    activity()
        ->createdAt($activityDateTime)
        ->log('created');

    $firstActivity = Activity::first();

    expect($firstActivity->created_at->toAtomString())->toEqual($activityDateTime->toAtomString());
});

it('will disable logs for a callback', function () {
    $result = activity()->withoutLogs(function () {
        activity()->log('created');

        return 'hello';
    });

    expect($this->getLastActivity())->toBeNull();
    expect($result)->toEqual('hello');
});

it('will disable logs for a callback without affecting previous state', function () {
    activity()->withoutLogs(function () {
        activity()->log('created');
    });

    expect($this->getLastActivity())->toBeNull();

    activity()->log('outer');

    expect($this->getLastActivity()->description)->toEqual('outer');
});

it('will disable logs for a callback without affecting previous state even when already disabled', function () {
    activity()->disableLogging();

    activity()->withoutLogs(function () {
        activity()->log('created');
    });

    expect($this->getLastActivity())->toBeNull();

    activity()->log('outer');

    expect($this->getLastActivity())->toBeNull();
});

it('will disable logs for a callback without affecting previous state even with exception', function () {
    activity()->disableLogging();

    try {
        activity()->withoutLogs(function () {
            activity()->log('created');

            throw new Exception('OH NO');
        });
    } catch (Exception $ex) {
        //
    }

    expect($this->getLastActivity())->toBeNull();

    activity()->log('outer');

    expect($this->getLastActivity())->toBeNull();
});

it('logs backed enums in properties', function () {
    activity()
        ->withProperties(['int_backed_enum' => \Spatie\Activitylog\Test\Enums\IntBackedEnum::Draft])
        ->withProperty('string_backed_enum', \Spatie\Activitylog\Test\Enums\StringBackedEnum::Published)
        ->log($this->activityDescription);

    $this->assertSame(0, $this->getLastActivity()->properties['int_backed_enum']);
    $this->assertSame('published', $this->getLastActivity()->properties['string_backed_enum']);
})->skip(version_compare(PHP_VERSION, '8.1', '<'), "PHP < 8.1 doesn't support enum");

it('does not log non backed enums in properties', function () {
    activity()
        ->withProperty('non_backed_enum', NonBackedEnum::Published)
        ->log($this->activityDescription);
})
    ->throws(JsonEncodingException::class)
    ->skip(version_compare(PHP_VERSION, '8.1', '<'), "PHP < 8.1 doesn't support enum");
