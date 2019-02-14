<?php

namespace Spatie\Activitylog\Test;

use Artisan;
use Carbon\Carbon;
use Spatie\Activitylog\Models\Activity;

class CleanActivitylogCommandTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2016, 1, 1, 00, 00, 00));

        $this->app['config']->set('activitylog.delete_records_older_than_days', 31);
    }

    /** @test */
    public function it_can_clean_the_activity_log()
    {
        collect(range(1, 60))->each(function (int $index) {
            Activity::create([
                'description' => "item {$index}",
                'created_at' => Carbon::now()->subDays($index)->startOfDay(),
            ]);
        });

        $this->assertCount(60, Activity::all());

        Artisan::call('activitylog:clean');

        $this->assertCount(31, Activity::all());

        $cutOffDate = Carbon::now()->subDays(31)->format('Y-m-d H:i:s');

        $this->assertCount(0, Activity::where('created_at', '<', $cutOffDate)->get());
    }

    /** @test */
    public function it_can_accept_days_as_option_to_override_config_setting()
    {
        collect(range(1, 60))->each(function (int $index) {
            Activity::create([
                'description' => "item {$index}",
                'created_at' => Carbon::now()->subDays($index)->startOfDay(),
            ]);
        });

        $this->assertCount(60, Activity::all());

        Artisan::call('activitylog:clean', ['--days' => 7]);

        $cutOffDate = Carbon::now()->subDay(7)->format('Y-m-d H:i:s');

        $this->assertCount(0, Activity::where('created_at', '<', $cutOffDate)->get());
    }
}
