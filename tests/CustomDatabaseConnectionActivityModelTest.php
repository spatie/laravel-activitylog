<?php

use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Test\Models\CustomDatabaseConnectionOnActivityModel;

it('uses the database connection from the configuration', function () {
    $model = new Activity();

    expect(config('activitylog.database_connection'))->toEqual($model->getConnectionName());
});

it('uses a custom database connection', function () {
    $model = new Activity();

    $model->setConnection('custom_sqlite');

    $this->assertNotEquals($model->getConnectionName(), config('activitylog.database_connection'));
    expect('custom_sqlite')->toEqual($model->getConnectionName());
});

it('uses the default database connection when the one from configuration is null', function () {
    app()['config']->set('activitylog.database_connection', null);

    $model = new Activity();

    expect($model->getConnection())->toBeInstanceOf('Illuminate\Database\SQLiteConnection');
});

it('uses the database connection from model', function () {
    $model = new CustomDatabaseConnectionOnActivityModel();

    $this->assertNotEquals($model->getConnectionName(), config('activitylog.database_connection'));
    expect('custom_connection_name')->toEqual($model->getConnectionName());
});
