<?php

namespace Spatie\Activitylog\Test;

use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Exceptions\CouldNotLogActivity;
use Spatie\Activitylog\Facades\CauserResolver;
use Spatie\Activitylog\Test\Models\Article;
use Spatie\Activitylog\Test\Models\User;

class CauserResolverTest extends TestCase
{
    /** @test */
    public function it_can_resolve_current_logged_in_user()
    {
        Auth::login($user = User::first());

        $causer = CauserResolver::resolve();

        $this->assertInstanceOf(User::class, $causer);
        $this->assertEquals($user->id, $causer->id);
    }

    /** @test */
    public function it_will_resolve_a_null_callback()
    {
        $causer = CauserResolver::resolve(fn () => null);

        $this->assertNull($causer);
    }


    /** @test */
    public function it_will_throw_an_exception_if_it_cannot_resolve_user_by_id()
    {
        $this->expectException(CouldNotLogActivity::class);

        CauserResolver::resolve(9999);
    }


    /** @test */
    public function it_will_throw_an_exception_if_callback_resolved_invalid_causer_type()
    {
        $this->expectException(CouldNotLogActivity::class);

        $invalidCauserType = new class() {
            public function __toString()
            {
                return 'invalidCauserType';
            }
        };

        CauserResolver::resolve(fn () => $invalidCauserType);
    }


    /** @test */
    public function it_can_resloved_user_from_passed_id()
    {
        $causer = CauserResolver::resolve(1);

        $this->assertInstanceOf(User::class, $causer);
        $this->assertEquals(1, $causer->id);
    }


    /** @test */
    public function it_will_resolve_the_provided_override_callback()
    {
        CauserResolver::withResolver(fn () => Article::first());

        $causer = CauserResolver::resolve();

        $this->assertInstanceOf(Article::class, $causer);
        $this->assertEquals(1, $causer->id);
    }


    /** @test */
    public function it_will_resolve_any_model()
    {
        $causer = CauserResolver::resolve($article = Article::first());

        $this->assertInstanceOf(Article::class, $causer);
        $this->assertEquals($article->id, $causer->id);
    }
}
