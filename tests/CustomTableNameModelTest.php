<?php

namespace Spatie\Activitylog\Test;

use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Test\Models\CustomTableNameOnActivityModel;

class CustomTableNameModelTest extends TestCase
{
    /** @test */
    public function it_uses_the_table_name_from_the_configuration()
    {
        $model = new Activity();

        $this->assertEquals($model->getTable(), config('activitylog.table_name'));
    }

    /** @test */
    public function it_uses_a_custom_table_name()
    {
        $model = new Activity();
        $new_table_name = 'my_personal_activities';

        $model->setTable($new_table_name);

        $this->assertNotEquals($model->getTable(), config('activitylog.table_name'));
        $this->assertEquals($model->getTable(), $new_table_name);
    }

    /** @test */
    public function it_uses_the_table_name_from_the_model()
    {
        $model = new CustomTableNameOnActivityModel();

        $this->assertNotEquals($model->getTable(), config('activitylog.table_name'));
        $this->assertEquals($model->getTable(), 'my_personal_activities');
    }
}
