<?php

namespace Spatie\Activitylog\Test;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Spatie\Activitylog\ActivitylogServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    public function setUp()
    {
        parent::setUp();
        
        $this->setUpDatabase();
    }

    protected function getPackageProviders($app)
    {
        return [
            ActivitylogServiceProvider::class
        ];
    }
    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');

        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => $this->getTempDirectory().'/database.sqlite',
            'prefix' => '',
        ]);
        $app['config']->set('app.key', '6rE9Nz59bGRbeMATftriyQjrpF7DcOQm');
    }
    protected function setUpDatabase()
    {
        file_put_contents($this->getTempDirectory().'/database.sqlite', null);

        $this->createActivityLogTable();
    }

    public function getTempDirectory(): string
    {
        return __DIR__.'/temp';
    }
}
