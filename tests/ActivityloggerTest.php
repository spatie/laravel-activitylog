<?php

namespace Spatie\Activitylog\Test;

use Spatie\Activitylog\Models\Activity;

class ActivityloggerTest extends TestCase
{
    /** @test */
    public function it_can_log_an_activity()
    {
        $activityDescription = 'My activity';

        activity()->log($activityDescription);

        $this->assertEquals($activityDescription, Activity::first()->description);
    }
}