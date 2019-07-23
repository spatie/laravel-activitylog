<?php

namespace Spatie\Activitylog\Test;

use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Test\Models\CustomDatabaseConnectionOnActivityModel;

class CustomDatabaseConnectionActivityModelTest extends TestCase
{
    /** @test */
    public function it_uses_the_database_connection_from_the_configuration()
    {
        $model = new Activity();

        $this->assertEquals($model->getConnectionName(), config('activitylog.database_connection'));
    }

    /** @test */
    public function it_uses_a_custom_database_connection()
    {
        $model = new Activity();

        $model->setConnection('custom_sqlite');

        $this->assertNotEquals($model->getConnectionName(), config('activitylog.database_connection'));
        $this->assertEquals($model->getConnectionName(), 'custom_sqlite');
    }

    /** @test */
    public function it_uses_the_database_connection_from_model()
    {
        $model = new CustomDatabaseConnectionOnActivityModel();

        $this->assertNotEquals($model->getConnectionName(), config('activitylog.database_connection'));
        $this->assertEquals($model->getConnectionName(), 'custom_connection_name');
    }
}
