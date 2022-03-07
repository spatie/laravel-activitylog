<?php

use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Test\Models\CustomTableNameOnActivityModel;

uses(TestCase::class);

it('uses the table name from the configuration', function () {
    $model = new Activity();

    $this->assertEquals($model->getTable(), config('activitylog.table_name'));
});

it('uses a custom table name', function () {
    $model = new Activity();
    $newTableName = 'my_personal_activities';

    $model->setTable($newTableName);

    $this->assertNotEquals($model->getTable(), config('activitylog.table_name'));
    $this->assertEquals($model->getTable(), $newTableName);
});

it('uses the table name from the model', function () {
    $model = new CustomTableNameOnActivityModel();

    $this->assertNotEquals($model->getTable(), config('activitylog.table_name'));
    $this->assertEquals($model->getTable(), 'custom_table_name');
});
