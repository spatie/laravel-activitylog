<?php

use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\CauserResolver;
use Spatie\Activitylog\Exceptions\CouldNotLogActivity;
use Spatie\Activitylog\Test\Models\Article;
use Spatie\Activitylog\Test\Models\User;

it('can resolve current logged in user', function () {
    Auth::login($user = User::first());

    $causer = app(CauserResolver::class)->resolve();

    expect($causer)->toBeInstanceOf(User::class);
    expect($causer->id)->toEqual($user->id);
});

it('will throw an exception if it cannot resolve user by id', function () {
    $this->expectException(CouldNotLogActivity::class);

    app(CauserResolver::class)->resolve(9999);
});

it('can resolve user from passed id', function () {
    $causer = app(CauserResolver::class)->resolve(1);

    expect($causer)->toBeInstanceOf(User::class);
    expect($causer->id)->toEqual(1);
});

it('will resolve the provided override callback', function () {
    app(CauserResolver::class)->resolveUsing(fn () => Article::first());

    $causer = app(CauserResolver::class)->resolve();

    expect($causer)->toBeInstanceOf(Article::class);
    expect($causer->id)->toEqual(1);
});

it('will resolve any model', function () {
    $causer = app(CauserResolver::class)->resolve($article = Article::first());

    expect($causer)->toBeInstanceOf(Article::class);
    expect($causer->id)->toEqual($article->id);
});
