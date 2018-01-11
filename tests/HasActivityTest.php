<?php

namespace Spatie\Activitylog\Test;

use Spatie\Activitylog\Test\Models\User;
use Spatie\Activitylog\Traits\HasActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Activity;

class HasActivityTest extends TestCase
{
    protected $user;

    public function setUp()
    {
        parent::setUp();

        $this->user = new class() extends User {
            use HasActivity;
            use SoftDeletes;
        };

        $this->assertCount(0, Activity::all());
    }

    /** @test */
    public function it_can_log_activity_on_subject_by_same_causer()
    {
        $user = $this->createUser();
        $this->assertCount(1, Activity::all());

        $this->assertInstanceOf(get_class($this->user), $this->getLastActivity()->subject);
        $this->assertEquals($user->id, $this->getLastActivity()->subject->id);
        $this->assertCount(1, $user->activity);
    }

    protected function createUser(): User
    {
        $user = new $this->user();
        $user->name = 'my name';
        $user->save();

        return $user;
    }
}
