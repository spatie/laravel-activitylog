<?php

namespace Spatie\Activitylog\Test;

use Auth;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Test\Models\User;
use Spatie\Activitylog\Test\Models\Article;
use Spatie\Activitylog\Test\Models\OtherGuardUser;

class CanUseMultiAuthTest extends TestCase
{
    /** @var string */
    protected $activityDescription;

    public function setUp()
    {
        $this->activityDescription = 'My activity';

        parent::setUp();
    }

    /** @test */
    public function it_uses_any_guards_user_used()
    {
        config(['auth.providers.users.model' => User::class]);

        config(['auth.providers.users.driver' => 'eloquent']);

        config(['auth.providers.other_guard_users.model' => OtherGuardUser::class]);

        config(['auth.providers.other_guard_users.driver' => 'eloquent']);

        config(['auth.guards.other_guard_user' => [
            'driver' => 'session',
            'provider' => 'other_guard_users',
        ]]);

        config(['auth.guards.web' => [
            'driver' => 'session',
            'provider' => 'users',
        ]]);

        $user = User::first();

        Auth::guard('web')->login($user);

        $loggedUser = Auth::guard('web')->user();

        $article = Article::create(['name' => 'article name']);

        activity()->log('User Activity is Logged');

        $firstActivity = Activity::all()->first();

        $this->assertEquals($loggedUser->id, $firstActivity->causer->id);

        $this->assertInstanceOf(User::class, $firstActivity->causer);

        Auth::guard('web')->logout();

        $otherGuardUser = OtherGuardUser::first();

        Auth::guard('other_guard_user')->login($otherGuardUser);

        $loggedOtherGuardUser = Auth::guard('other_guard_user')->user();

        $article = Article::create(['name' => 'article name']);

        activity()->log('Other Guard User Activity is Logged');

        $lastActivity = Activity::all()->last();

        $this->assertEquals($loggedOtherGuardUser->id, $lastActivity->causer->id);

        $this->assertInstanceOf(OtherGuardUser::class, $lastActivity->causer);
    }
}
