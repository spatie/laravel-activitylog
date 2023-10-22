<?php

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Test\Models\User;
use Spatie\Activitylog\Test\Models\Article;
use Spatie\Activitylog\Traits\LogsActivity;

it('can fake logging activities if asked to', function () {
    activity()->fake();

    activity()->log('This will not be persisted to the database');

    $this->assertCount(0, Activity::all());
    $this->assertNull($this->getLastActivity());
});

it('can fake logging of model events if asked to', function () {
    activity()->fake();

    $this->article = new class() extends Article {
        use LogsActivity;

        public function getActivitylogOptions(): LogOptions
        {
            return LogOptions::defaults()
            ->logOnly(['name', 'text']);
        }
    };

    $this->createArticle();

    $this->assertCount(0, Activity::all());
    $this->assertNull($this->getLastActivity());
});

it('can test whether activities were logged', function () {
    activity()->fake();

    activity()->log('Something happened');
    activity()->log('Something else happened');
    activity()->log('Things keep happening!');

    activity()->assertLogged();
    activity()->assertLogged(3);
});

it('can test whether no activities were logged', function () {
    activity()->fake();

    activity()->assertNothingLogged();
    activity()->assertLogged(0);
});

it('can test whether activities were logged to a particular log', function () {
    activity()->fake();

    activity('first log')->log('Something happened');
    activity('second log')->log('Something else happened');
    activity('second log')->log('Things keep happening!');

    activity()->assertLoggedToLog('first log');
    activity()->assertLoggedToLog('second log', 2);
    activity()->assertNothingLoggedToLog('the mythical third log');
});

it('can test whether activities were logged with a particular description', function () {
    activity()->fake();

    activity()->log('Something happened');
    activity()->log('Something happened');
    activity()->log('Something else happened, but nobody was interested about it :(');

    activity()->assertLoggedWithDescription('Something happened');
    activity()->assertLoggedWithDescription('Something happened', 2);
    activity()->assertNothingLoggedWithDescription('This thing did not happen at all');
});

it('can test whether activities were logged with a particular event', function () {
    activity()->fake();

    activity()->event('ThingEvent')->log('Something happened');
    activity()->event('OtherThingEvent')->log('Something else happened');
    activity()->event('OtherThingEvent')->log('Things keep happening!');

    activity()->assertLoggedWithEvent('ThingEvent');
    activity()->assertLoggedWithEvent('ThingEvent', 1);
    activity()->assertLoggedWithEvent('OtherThingEvent', 2);
    activity()->assertNothingLoggedWithEvent('DidNotHappenEvent');
});

it('can test whether activities were logged with a particular subject', function () {
    activity()->fake();

    $user = new User(['id' => 111]);
    $otherUser = new User(['id' => 222]);

    activity()->performedOn($user)->log('Something happened');
    activity()->performedOn($user)->log('Something else happened');
    activity()->performedOn($user)->log('Things keep happening to this poor user!');
    activity()->performedOn($otherUser)->log('Something happened to this other user, but nobody cares :(');

    activity()->assertLoggedWithSubjectType(User::class);
    activity()->assertLoggedWithSubjectType(User::class, 4);
    activity()->assertNothingLoggedWithSubjectType('NonUser');

    activity()->assertLoggedWithSubjectId(111);
    activity()->assertLoggedWithSubjectId(111, 3);
    activity()->assertNothingLoggedWithSubjectId(333);
});

it('can test whether activities were logged with a particular causer', function () {
    activity()->fake();

    $user = new User(['id' => 111]);
    $otherUser = new User(['id' => 222]);

    activity()->causedBy($user)->log('Something happened');
    activity()->causedBy($user)->log('Something else happened');
    activity()->causedBy($user)->log('This user keeps making things happen!');
    activity()->causedBy($otherUser)->log('This other user makes things happen too, but nobody cares :(');

    activity()->assertLoggedWithCauserType(User::class);
    activity()->assertLoggedWithCauserType(User::class, 4);
    activity()->assertNothingLoggedWithCauserType('NonUser');

    activity()->assertLoggedWithCauserId(111);
    activity()->assertLoggedWithCauserId(111, 3);
    activity()->assertNothingLoggedWithCauserId(333);
});

it('can test whether activities were logged with a particular set of properties', function () {
    activity()->fake();

    $properties = ['one' => 'une', 'two' => 'deux'];
    $propertiesAndMore = ['one' => 'une', 'two' => 'deux', 'three' => 'trois'];
    $differentProperties = ['one' => 'uno', 'two' => 'due'];
    $unusedProperties = ['one' => 'uno', 'two' => 'dos'];

    activity()->withProperties($properties)->log('Something happened');
    activity()->withProperties($properties)->log('Something else happened');
    activity()->withProperties($propertiesAndMore)->log('Things keep happening, sometimes with even more properties!');
    activity()->withProperties($differentProperties)->log('This thing happened too, but nobody cares about it :(');

    activity()->assertLoggedWithProperties($properties);
    activity()->assertLoggedWithProperties($properties, 2);
    activity()->assertNothingLoggedWithProperties($unusedProperties);
});

it('can test whether activities were logged that include particular properties', function () {
    activity()->fake();

    $properties = ['one' => 'une', 'two' => 'deux'];
    $propertiesAndMore = ['one' => 'une', 'two' => 'deux', 'three' => 'trois'];
    $differentProperties = ['one' => 'uno', 'two' => 'due'];
    $unusedProperties = ['one' => 'uno', 'two' => 'dos'];

    activity()->withProperties($properties)->log('Something happened');
    activity()->withProperties($properties)->log('Something else happened');
    activity()->withProperties($propertiesAndMore)->log('Things keep happening, sometimes with even more properties!');
    activity()->withProperties($differentProperties)->log('This thing happened too, but nobody cares about it :(');

    activity()->assertLoggedIncludingProperties($properties);
    activity()->assertLoggedIncludingProperties($properties, 3);
    activity()->assertNothingLoggedIncludingProperties($unusedProperties);
});

it('can test whether activities were logged based on a custom test', function () {
    activity()->fake();

    $properties = ['one' => 'une', 'two' => 'deux'];

    activity()->withProperties($properties)->log('Something normal happened');
    activity()->withProperties($properties)->log('Something else normal happened');
    activity()->withProperties($properties)->log('Something weird happened, with those same properties');

    $testOne = function ($activity) use ($properties) {
        return
            collect([$activity->properties])->contains('one', 'une')
            && strpos($activity->description, 'normal') !== false;
    };

    $testTwo = function ($activity) {
        return strpos($activity->description, 'Rumpelstiltskin') !== false;
    };

    activity()->assertLoggedMatching($testOne);
    activity()->assertLoggedMatching($testOne, 2);
    activity()->assertNothingLoggedMatching($testTwo);
});
