<?php

use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Test\Models\CustomDatabaseConnectionOnActivityModel;

uses(TestCase::class);

it('uses the database connection from the configuration', function () {
    $model = new Activity();

    $this->assertEquals($model->getConnectionName(), config('activitylog.database_connection'));
});

it('uses a custom database connection', function () {
    $model = new Activity();

    $model->setConnection('custom_sqlite');

    $this->assertNotEquals($model->getConnectionName(), config('activitylog.database_connection'));
    $this->assertEquals($model->getConnectionName(), 'custom_sqlite');
});

it('uses the default database connection when the one from configuration is null', function () {
    app()['config']->set('activitylog.database_connection', null);

    $model = new Activity();

    $this->assertInstanceOf('Illuminate\Database\SQLiteConnection', $model->getConnection());
});

it('uses the database connection from model', function () {
    $model = new CustomDatabaseConnectionOnActivityModel();

    $this->assertNotEquals($model->getConnectionName(), config('activitylog.database_connection'));
    $this->assertEquals($model->getConnectionName(), 'custom_connection_name');
});
