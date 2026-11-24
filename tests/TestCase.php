<?php

namespace Spatie\Activitylog\Test;

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\MongoDBServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Spatie\Activitylog\ActivitylogServiceProvider;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Test\Models\Article;
use Spatie\Activitylog\Test\Models\User;

abstract class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function getPackageProviders($app)
    {
        return [
            MongoDBServiceProvider::class,
            ActivitylogServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        $mongoConnection = [
            'driver' => 'mongodb',
            'dsn' => env('MONGODB_URI', 'mongodb://127.0.0.1:27017'),
            'database' => env('MONGODB_DATABASE', 'activitylog_test'),
        ];

        config()->set('database.default', 'mongodb');
        config()->set('database.connections.mongodb', $mongoConnection);
        config()->set('database.connections.custom_connection_name', $mongoConnection);
        config()->set('database.connections.unrelated', $mongoConnection);

        config()->set('auth.providers.users.model', User::class);
        config()->set('app.key', 'base64:'.base64_encode(
            Encrypter::generateKey(config()['app.cipher'])
        ));
    }

    protected function setUpDatabase(): void
    {
        Schema::connection('mongodb')->dropAllTables();

        $this->migrateActivityLogTable();

        $this->createTables('articles', 'users');
        $this->seedModels(Article::class, User::class);
    }

    protected function migrateActivityLogTable(): void
    {
        $migration = require __DIR__.'/../database/migrations/create_activity_log_table.php.stub';
        $migration->up();
    }

    protected function createTables(...$tableNames): void
    {
        collect($tableNames)->each(function (string $tableName): void {
            Schema::connection('mongodb')->create($tableName);
        });
    }

    protected function seedModels(...$modelClasses): void
    {
        collect($modelClasses)->each(function (string $modelClass): void {
            foreach (range(1, 0) as $index) {
                $modelClass::create(['_id' => $index, 'name' => "name {$index}"]);
            }
        });
    }

    public function getLastActivity(): ?Activity
    {
        return Activity::all()->last();
    }

    public function markTestAsPassed(): void
    {
        $this->assertTrue(true);
    }

    public function createArticle(): Article
    {
        $article = new $this->article();
        $article->name = 'my name';
        $article->save();

        return $article;
    }

    public function loginWithFakeUser()
    {
        $user = new $this->user();

        $user::find(1);

        $this->be($user);

        return $user;
    }
}
