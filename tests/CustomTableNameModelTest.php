<?php

use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Test\Models\CustomTableNameOnActivityModel;

it('uses the table name from the configuration', function () {
    $model = new Activity();

    expect(config('activitylog.table_name'))->toEqual($model->getTable());
});

it('uses a custom table name', function () {
    $model = new Activity();
    $newTableName = 'my_personal_activities';

    $model->setTable($newTableName);

    $this->assertNotEquals($model->getTable(), config('activitylog.table_name'));
    expect($newTableName)->toEqual($model->getTable());
});

it('uses the table name from the model', function () {
    $model = new CustomTableNameOnActivityModel();

    $this->assertNotEquals($model->getTable(), config('activitylog.table_name'));
    expect('custom_table_name')->toEqual($model->getTable());
});
