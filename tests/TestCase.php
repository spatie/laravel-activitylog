<?php

namespace Spatie\Activitylog\Test;

use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Test\Models\User;
use Spatie\Activitylog\Test\Models\OtherGuardUser;
use Illuminate\Database\Schema\Blueprint;
use Spatie\Activitylog\Test\Models\Article;
use Spatie\Activitylog\ActivitylogServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function checkRequirements()
    {
        parent::checkRequirements();

        collect($this->getAnnotations())->filter(function ($location) {
            return in_array('!Travis', array_get($location, 'requires', []));
        })->each(function ($location) {
            getenv('TRAVIS') && $this->markTestSkipped('Travis will not run this test.');
        });
    }

    protected function getPackageProviders($app)
    {
        return [
            ActivitylogServiceProvider::class,
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

        $app['config']->set('auth.providers.users.model', User::class);

        $app['config']->set('auth.providers.other_guard_users.model', OtherGuardUser::class);

        $app['config']->set('app.key', '6rE9Nz59bGRbeMATftriyQjrpF7DcOQm');
    }

    protected function setUpDatabase()
    {
        $this->resetDatabase();

        $this->createActivityLogTable();

        $this->createTables('articles', 'users', 'other_guard_users');

        $this->seedModels(Article::class, User::class, OtherGuardUser::class);
    }

    protected function resetDatabase()
    {
        file_put_contents($this->getTempDirectory().'/database.sqlite', null);
    }

    protected function createActivityLogTable()
    {
        include_once '__DIR__'.'/../migrations/create_activity_log_table.php.stub';

        (new \CreateActivityLogTable())->up();
    }

    public function getTempDirectory(): string
    {
        return __DIR__.'/temp';
    }

    protected function createTables(...$tableNames)
    {
        collect($tableNames)->each(function (string $tableName) {
            $this->app['db']->connection()->getSchemaBuilder()->create($tableName, function (Blueprint $table) use ($tableName) {
                $table->increments('id');
                $table->string('name')->nullable();
                $table->string('text')->nullable();
                $table->timestamps();
                $table->softDeletes();

                if ($tableName === 'articles') {
                    $table->integer('user_id')->unsigned()->nullable();
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                    $table->text('json')->nullable();
                }
            });
        });
    }

    protected function seedModels(...$modelClasses)
    {
        collect($modelClasses)->each(function (string $modelClass) {
            foreach (range(1, 0) as $index) {
                $modelClass::create(['name' => "name {$index}"]);
            }
        });
    }

    /**
     * @return \Spatie\Activitylog\Models\Activity|null
     */
    public function getLastActivity()
    {
        return Activity::all()->last();
    }
}
