<?php

namespace Spatie\Activitylog\Test;

use AddScopesToActivityLogTable;
use Spatie\Activitylog\Test\Models\Article;
use Spatie\Activitylog\Traits\LogsActivity;

class ActivityScopeTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->addScopesToActivityLogTable();
    }

    protected function addScopesToActivityLogTable()
    {
        include_once __DIR__.'/migrations/add_scopes_to_activity_log_table.php';

        (new AddScopesToActivityLogTable())->up();
    }

    /** @test */
    public function it_can_log_additional_scope_for_event()
    {
        $this->app['config']->set('activitylog.scope_fields', ['user_id']);

        $articleClass = new class() extends Article {
            use LogsActivity;

            protected function logScope(): array
            {
                return [
                    'user_id' => 123,
                ];
            }
        };
        $article = $articleClass::create();

        $this->assertEquals(123, $this->getLastActivity()->user_id);
    }

    /** @test */
    public function it_can_ignore_not_configured_scopes()
    {
        $this->app['config']->set('activitylog.scope_fields', []);

        $articleClass = new class() extends Article {
            use LogsActivity;

            protected function logScope(): array
            {
                return [
                    'user_id' => 123,
                ];
            }
        };
        $article = $articleClass::create();

        $this->assertNull($this->getLastActivity()->user_id);
    }
}
