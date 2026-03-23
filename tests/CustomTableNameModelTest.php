<?php

use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Test\Models\CustomTableNameOnActivityModel;

it('uses activity_log as the default table name', function () {
    $model = new Activity();

    expect($model->getTable())->toEqual('activity_log');
});

it('uses the table name from a custom model', function () {
    $model = new CustomTableNameOnActivityModel();

    expect($model->getTable())->toEqual('custom_table_name');
});
