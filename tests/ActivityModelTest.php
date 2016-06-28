<?php

namespace Spatie\Activitylog\Test;

use Spatie\Activitylog\Models\Activity;

class ActivityModelTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        collect(range(1,5))->each(function (int $index) {
            $logName = "log{$index}";
            activity($logName)->log("hello everybody");
        });
    }

    /** @test */
    public function it_provides_a_scope_to_get_activities_from_a_specific_log()
    {
        $activityOnLog3 = Activity::onLog('log3')->get();

        $this->assertCount(1, $activityOnLog3);

        $this->assertEquals("log3", $activityOnLog3->first()->log_name);
    }

    /** @test */
    public function it_provides_a_scope_to_get_log_items_from_multiple_logs()
    {
        $activity = Activity::onLog('log2', 'log4')->get();

        $this->assertCount(2, $activity);

        $this->assertEquals("log2", $activity->first()->log_name);
        $this->assertEquals("log4", $activity->last()->log_name);
    }

    /** @test */
    public function it_provides_a_scope_to_get_log_items_from_multiple_logs_using_an_array()
    {
        $activity = Activity::onLog(['log1', 'log2'])->get();

        $this->assertCount(2, $activity);

        $this->assertEquals("log1", $activity->first()->log_name);
        $this->assertEquals("log2", $activity->last()->log_name);
    }
}
