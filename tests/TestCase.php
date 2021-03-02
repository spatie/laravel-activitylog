<?php

namespace Spatie\Activitylog\Test;

use CreateActivityLogTable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use PHPUnit\Util\Test;
use Spatie\Activitylog\ActivitylogServiceProvider;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Test\Models\Article;
use Spatie\Activitylog\Test\Models\User;

abstract class TestCase extends OrchestraTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function getPackageProviders($app)
    {
        return [
            ActivitylogServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('activitylog.database_connection', 'sqlite');
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('app.key', 'base64:'.base64_encode(
            Encrypter::generateKey($app['config']['app.cipher'])
        ));
    }

    protected function setUpDatabase()
    {
        $this->createActivityLogTable();

        $this->createTables('articles', 'users');
        $this->seedModels(Article::class, User::class);
    }

    protected function createActivityLogTable()
    {
        include_once __DIR__.'/../migrations/create_activity_log_table.php.stub';

        (new CreateActivityLogTable())->up();
    }

    protected function createTables(...$tableNames)
    {
        collect($tableNames)->each(function (string $tableName) {
            Schema::create($tableName, function (Blueprint $table) use ($tableName) {
                $table->increments('id');
                $table->string('name')->nullable();
                $table->string('text')->nullable();
                $table->timestamps();
                $table->softDeletes();

                if ($tableName === 'articles') {
                    $table->integer('user_id')->unsigned()->nullable();
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                    $table->text('json')->nullable();
                    $table->decimal('price')->nullable();
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

    public function getLastActivity(): ?Activity
    {
        return Activity::all()->last();
    }

    public function markTestAsPassed()
    {
        $this->assertTrue(true);
    }

    public function isLaravel6OrLower(): bool
    {
        $majorVersion = (int) substr(App::version(), 0, 1);

        return $majorVersion <= 6;
    }
}
