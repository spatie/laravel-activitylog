<?php

namespace Spatie\Activitylog\Test;

use Spatie\Activitylog\ActivityLoggerBatch;

class ActivityLoggerBatchTest extends TestCase
{

    /** @test */
    public function it_generates_uuid_after_start_and_end_batch_properely()
    {
        app(ActivityLoggerBatch::class)->startBatch();
        $uuid = app(ActivityLoggerBatch::class)->getUuid();
        app(ActivityLoggerBatch::class)->endBatch();


        $this->assertNotEmpty($uuid);
    }

    /** @test */
    public function it_returns_null_uuid_after_end_batch_properely()
    {
        app(ActivityLoggerBatch::class)->startBatch();
        $uuid = app(ActivityLoggerBatch::class)->getUuid();
        app(ActivityLoggerBatch::class)->endBatch();


        $this->assertNotNull($uuid);
        $this->assertNull(app(ActivityLoggerBatch::class)->getUuid());
    }


    /** @test */
    public function it_generates_a_new_uuid_after_starting_new_batch_properly()
    {
        app(ActivityLoggerBatch::class)->startBatch();
        $firstBatchUuid = app(ActivityLoggerBatch::class)->getUuid();
        app(ActivityLoggerBatch::class)->endBatch();

        app(ActivityLoggerBatch::class)->startBatch();
        $secondBatchUuid = app(ActivityLoggerBatch::class)->getUuid();
        app(ActivityLoggerBatch::class)->endBatch();


        $this->assertNotNull($firstBatchUuid);
        $this->assertNotNull($secondBatchUuid);

        $this->assertNotEquals($firstBatchUuid, $secondBatchUuid);
    }


    /** @test */
    public function it_will_not_generate_new_uuid_if_start_already_started_batch()
    {
        app(ActivityLoggerBatch::class)->startBatch();

        $firstUuid = app(ActivityLoggerBatch::class)->getUuid();

        app(ActivityLoggerBatch::class)->startBatch();

        $secondUuid = app(ActivityLoggerBatch::class)->getUuid();

        app(ActivityLoggerBatch::class)->endBatch();


        $this->assertEquals($firstUuid, $secondUuid);
    }


    /** @test */
    public function it_will_not_generate_uuid_if_end_batch_before_starting()
    {
        app(ActivityLoggerBatch::class)->endBatch();
        $uuid = app(ActivityLoggerBatch::class)->getUuid();

        app(ActivityLoggerBatch::class)->startBatch();

        $this->assertNull($uuid);
    }

    /** @test */
    public function it_will_not_return_null_uuid_if_end_batch_that_started_twice()
    {
        app(ActivityLoggerBatch::class)->startBatch();
        $firstUuid = app(ActivityLoggerBatch::class)->getUuid();

        app(ActivityLoggerBatch::class)->startBatch();

        app(ActivityLoggerBatch::class)->endBatch();

        $notNullUuid = app(ActivityLoggerBatch::class)->getUuid();


        $this->assertNotNull($firstUuid);
        $this->assertNotNull($notNullUuid);

        $this->assertSame($firstUuid, $notNullUuid);
    }

    /** @test */
    public function it_will_return_null_uuid_if_end_batch_that_started_twice_properly()
    {
        app(ActivityLoggerBatch::class)->startBatch();
        $firstUuid = app(ActivityLoggerBatch::class)->getUuid();

        app(ActivityLoggerBatch::class)->startBatch();

        app(ActivityLoggerBatch::class)->endBatch();
        app(ActivityLoggerBatch::class)->endBatch();

        $nullUuid = app(ActivityLoggerBatch::class)->getUuid();

        $this->assertNotNull($firstUuid);
        $this->assertNull($nullUuid);

        $this->assertNotSame($firstUuid, $nullUuid);
    }


    /** @test */
    public function batch_stress_test()
    {
        app(ActivityLoggerBatch::class)->startBatch();
        app(ActivityLoggerBatch::class)->startBatch();
        app(ActivityLoggerBatch::class)->startBatch();

        $firstUuid = app(ActivityLoggerBatch::class)->getUuid();

        app(ActivityLoggerBatch::class)->endBatch();

        $firstUuidAfterFirstEnd = app(ActivityLoggerBatch::class)->getUuid();

        app(ActivityLoggerBatch::class)->endBatch();

        $firstUuidAfterSecondEnd = app(ActivityLoggerBatch::class)->getUuid();

        app(ActivityLoggerBatch::class)->endBatch();

        $firstUuidAfterThirdEnd = app(ActivityLoggerBatch::class)->getUuid();

        app(ActivityLoggerBatch::class)->endBatch();
        app(ActivityLoggerBatch::class)->endBatch();
        app(ActivityLoggerBatch::class)->endBatch();
        app(ActivityLoggerBatch::class)->endBatch();

        $stillNullUuid = app(ActivityLoggerBatch::class)->getUuid();

        app(ActivityLoggerBatch::class)->startBatch();

        app(ActivityLoggerBatch::class)->startBatch();

        $secondUuid = app(ActivityLoggerBatch::class)->getUuid();

        app(ActivityLoggerBatch::class)->endBatch();

        $SameSecondUuid = app(ActivityLoggerBatch::class)->getUuid();

        app(ActivityLoggerBatch::class)->endBatch();

        $nullSecondUuid = app(ActivityLoggerBatch::class)->getUuid();


        $this->assertNotNull($firstUuid);

        $this->assertEquals($firstUuid, $firstUuidAfterFirstEnd);
        $this->assertEquals($firstUuid, $firstUuidAfterSecondEnd);
        $this->assertNull($firstUuidAfterThirdEnd);
        $this->assertNull($stillNullUuid);
        $this->assertEquals($secondUuid, $SameSecondUuid);
        $this->assertNull($nullSecondUuid);
    }
}
