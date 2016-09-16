<?php

namespace Spatie\Activitylog\Test;

use Spatie\Activitylog\Exceptions\InvalidConfiguration;
use Spatie\Activitylog\Test\Models\CustomActivityModel;
use Spatie\Activitylog\Test\Models\InvalidActivityModel;

class CustomActivityModelTest extends TestCase
{
    /** @var string */
    protected $activityDescription;

    public function setUp()
    {
        $this->activityDescription = 'My activity';
        parent::setUp();

        collect(range(1, 5))->each(function (int $index) {
            $logName = "log{$index}";
            activity($logName)->log('hello everybody');
        });
    }

    /** @test */
    public function it_can_log_activity_using_a_custom_model()
    {
        $this->app['config']->set('laravel-activitylog.activity_model', CustomActivityModel::class);

        $activity = activity()->log($this->activityDescription);

        $this->assertEquals($this->activityDescription, $activity->description);

        $this->assertInstanceOf(CustomActivityModel::class, $activity);
    }

    /** @test */
    public function it_does_not_throw_an_exception_when_model_config_is_null()
    {
        $this->app['config']->set('laravel-activitylog.activity_model', null);

        activity()->log($this->activityDescription);
    }

    /** @test */
    public function it_throws_an_exception_when_model_doesnt_extend_package_model()
    {
        $this->app['config']->set('laravel-activitylog.activity_model', InvalidActivityModel::class);

        $this->expectException(InvalidConfiguration::class);

        activity()->log($this->activityDescription);
    }
}
