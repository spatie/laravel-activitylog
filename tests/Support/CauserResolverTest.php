<?php

use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Exceptions\CouldNotLogActivity;
use Spatie\Activitylog\Facades\Activity;
use Spatie\Activitylog\Support\CauserResolver;
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

it('can scope a causer using withCauser', function () {
    $user = User::first();
    $resolver = app(CauserResolver::class);

    $result = $resolver->withCauser($user, function () use ($resolver) {
        return $resolver->resolve();
    });

    expect($result)->toBeInstanceOf(User::class);
    expect($result->id)->toEqual($user->id);
});

it('restores the previous causer after withCauser', function () {
    $user1 = User::first();
    $user2 = User::find(2);
    $resolver = app(CauserResolver::class);

    $resolver->setCauser($user1);

    $resolver->withCauser($user2, function () use ($resolver, $user2) {
        expect($resolver->resolve()->id)->toEqual($user2->id);
    });

    expect($resolver->resolve()->id)->toEqual($user1->id);
});

it('restores the previous causer after withCauser even when an exception is thrown', function () {
    $user1 = User::first();
    $user2 = User::find(2);
    $resolver = app(CauserResolver::class);

    $resolver->setCauser($user1);

    try {
        $resolver->withCauser($user2, function () {
            throw new RuntimeException('test');
        });
    } catch (RuntimeException) {
        // expected
    }

    expect($resolver->resolve()->id)->toEqual($user1->id);
});

it('setCauser takes priority over resolveUsing', function () {
    $user = User::first();
    $article = Article::first();
    $resolver = app(CauserResolver::class);

    $resolver->resolveUsing(fn () => $article);
    $resolver->setCauser($user);

    $causer = $resolver->resolve();

    expect($causer)->toBeInstanceOf(User::class);
    expect($causer->id)->toEqual($user->id);
});

it('can set a default causer via the facade', function () {
    $user = User::first();

    Activity::defaultCauser($user);

    activity()->log('test with default causer');

    $activity = Spatie\Activitylog\Models\Activity::all()->last();

    expect($activity->causer)->toBeInstanceOf(User::class);
    expect($activity->causer->id)->toEqual($user->id);
});

it('can scope a default causer via the facade with a callback', function () {
    $user = User::first();

    Activity::defaultCauser($user, function () {
        activity()->log('scoped causer');
    });

    $activity = Spatie\Activitylog\Models\Activity::all()->last();

    expect($activity->causer)->toBeInstanceOf(User::class);
    expect($activity->causer->id)->toEqual($user->id);
});

it('restores the previous causer after the facade callback', function () {
    $user1 = User::first();
    $user2 = User::find(2);

    Activity::defaultCauser($user1);

    Activity::defaultCauser($user2, function () {
        activity()->log('inner');
    });

    activity()->log('outer');

    $activities = Spatie\Activitylog\Models\Activity::all();

    expect($activities[0]->causer->id)->toEqual($user2->id);
    expect($activities[1]->causer->id)->toEqual($user1->id);
});
