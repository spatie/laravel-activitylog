<?php
namespace Spatie\Activitylog\Test;


use Spatie\Activitylog\Exceptions\ModelMismatchException;
use Spatie\Activitylog\Test\Models\MyActivity;
use Spatie\Activitylog\Test\Models\TestActivityModel;

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

    /**
     * @test
     */
    public function it_can_log_an_activity()
    {
        $this->app['config']->set('laravel-activitylog.activity_model', MyActivity::class);
        $activity = activity()->log($this->activityDescription);
        $this->assertEquals($this->activityDescription, $this->getLastActivity()->description);
        $this->assertEquals("Spatie\\Activitylog\\Test\\Models\\MyActivity", $activity->getActivityModel());
    }


    /** @test */
    public function it_provides_a_scope_to_get_activities_from_a_specific_log()
    {
        $activityInLog3 = MyActivity::inLog('log3')->get();

        $this->assertCount(1, $activityInLog3);

        $this->assertEquals('log3', $activityInLog3->first()->log_name);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_model_config_is_null()
    {
        $this->app['config']->set('laravel-activitylog.activity_model', null);
        try {
            activity()->log($this->activityDescription);
            $this->fail("Exception not being thrown");
        } catch(ModelMismatchException $e) {
            $this->assertEquals("Model not set in laravel-activitylog.php", $e->getMessage());
        }

    }

    /** @test */
    public function it_throws_an_exception_when_model_doesnt_extend_package_model()
    {
        $this->app['config']->set('laravel-activitylog.activity_model', TestActivityModel::class);
        try {
            activity()->log($this->activityDescription);
            $this->fail("Exception not being thrown");
        } catch(ModelMismatchException $e) {
            $this->assertEquals("Model `Spatie\\Activitylog\\Test\\Models\\TestActivityModel` is not extending \\Spatie\\Activitylog\\Models\\Activity", $e->getMessage());
        }
    }
}