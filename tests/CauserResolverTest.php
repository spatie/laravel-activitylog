<?php

use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Exceptions\CouldNotLogActivity;
use Spatie\Activitylog\Facades\CauserResolver;
use Spatie\Activitylog\Test\Models\Article;
use Spatie\Activitylog\Test\Models\User;

it('can resolve current logged in user', function () {
    Auth::login($user = User::first());

    $causer = CauserResolver::resolve();

    expect($causer)->toBeInstanceOf(User::class);
    expect($causer->id)->toEqual($user->id);
});

it('will throw an exception if it cannot resolve user by id', function () {
    $this->expectException(CouldNotLogActivity::class);

    CauserResolver::resolve(9999);
});

it('can resloved user from passed id', function () {
    $causer = CauserResolver::resolve(1);

    expect($causer)->toBeInstanceOf(User::class);
    expect($causer->id)->toEqual(1);
});

it('will resolve the provided override callback', function () {
    CauserResolver::resolveUsing(fn () => Article::first());

    $causer = CauserResolver::resolve();

    expect($causer)->toBeInstanceOf(Article::class);
    expect($causer->id)->toEqual(1);
});

it('will resolve any model', function () {
    $causer = CauserResolver::resolve($article = Article::first());

    expect($causer)->toBeInstanceOf(Article::class);
    expect($causer->id)->toEqual($article->id);
});

it('will throw an exception it there is no auth manager to resolve', function () {
    // simulates application not having AuthServiceProvider loaded
    app()->singleton('auth', fn () => null);

    $causer = CauserResolver::resolve();
    expect($causer)->toBeNull();

    $this->expectExceptionObject(CouldNotLogActivity::couldNotDetermineUserWithoutAuthManager($subject = 9999));

    $causer = CauserResolver::resolve($subject);
    expect($causer)->toBeNull();
});
