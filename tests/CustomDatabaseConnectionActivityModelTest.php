<?php

use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Test\Models\CustomDatabaseConnectionOnActivityModel;

it('uses the default database connection', function () {
    $model = new Activity();

    expect($model->getConnection())->toBeInstanceOf('Illuminate\Database\SQLiteConnection');
});

it('uses the database connection from a custom model', function () {
    $model = new CustomDatabaseConnectionOnActivityModel();

    expect($model->getConnectionName())->toEqual('custom_connection_name');
});
