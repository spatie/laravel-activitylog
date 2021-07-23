<?php

namespace Spatie\Activitylog\Test;

use Spatie\Activitylog\Facades\LogBatch;
use Illuminate\Support\Str;

class LogBatchTest extends TestCase
{
    /** @test */
    public function it_generates_uuid_after_start_and_end_batch_properely()
    {
        LogBatch::startBatch();
        $uuid = LogBatch::getUuid();
        LogBatch::endBatch();

        $this->assertFalse(LogBatch::isopen());

        $this->assertIsString($uuid);
    }

    /** @test */
    public function it_returns_null_uuid_after_end_batch_properely()
    {
        LogBatch::startBatch();
        $uuid = LogBatch::getUuid();
        LogBatch::endBatch();

        $this->assertFalse(LogBatch::isopen());
        $this->assertNotNull($uuid);
        $this->assertNull(LogBatch::getUuid());
    }

    /** @test */
    public function it_generates_a_new_uuid_after_starting_new_batch_properly()
    {
        LogBatch::startBatch();
        $firstBatchUuid = LogBatch::getUuid();
        LogBatch::endBatch();

        LogBatch::startBatch();

        LogBatch::startBatch();
        $secondBatchUuid = LogBatch::getUuid();
        LogBatch::endBatch();

        $this->assertTrue(LogBatch::isopen());
        $this->assertNotNull($firstBatchUuid);
        $this->assertNotNull($secondBatchUuid);

        $this->assertNotEquals($firstBatchUuid, $secondBatchUuid);
    }

    /** @test */
    public function it_will_not_generate_new_uuid_if_start_already_started_batch()
    {
        LogBatch::startBatch();

        $firstUuid = LogBatch::getUuid();

        LogBatch::startBatch();

        $secondUuid = LogBatch::getUuid();

        LogBatch::endBatch();

        $this->assertTrue(LogBatch::isopen());

        $this->assertEquals($firstUuid, $secondUuid);
    }

    /** @test */
    public function it_will_not_generate_uuid_if_end_batch_before_starting()
    {
        LogBatch::endBatch();
        $uuid = LogBatch::getUuid();

        LogBatch::startBatch();

        $this->assertNull($uuid);
    }

    /** @test */
    public function it_can_set_uuid_and_start_a_batch()
    {
        $uuid = Str::uuid();

        LogBatch::setBatch($uuid);
        $this->assertTrue(LogBatch::isOpen());
        $this->assertEquals($uuid, LogBatch::getUuid());

        LogBatch::endBatch();
        $this->assertFalse(LogBatch::isOpen());
    }

    /** @test */
    public function it_can_set_uuid_for_already_started_batch()
    {
        $uuid = Str::uuid();

        LogBatch::startBatch();
        $this->assertTrue(LogBatch::isOpen());
        $this->assertNotEquals($uuid, LogBatch::getUuid());

        LogBatch::setBatch($uuid);
        $this->assertTrue(LogBatch::isOpen());
        $this->assertEquals($uuid, LogBatch::getUuid());

        LogBatch::endBatch();
        $this->assertFalse(LogBatch::isOpen());
    }

    /** @test */
    public function it_will_not_return_null_uuid_if_end_batch_that_started_twice()
    {
        LogBatch::startBatch();
        $firstUuid = LogBatch::getUuid();

        LogBatch::startBatch();

        LogBatch::endBatch();

        $notNullUuid = LogBatch::getUuid();

        $this->assertNotNull($firstUuid);
        $this->assertNotNull($notNullUuid);

        $this->assertSame($firstUuid, $notNullUuid);
    }

    /** @test */
    public function it_will_return_null_uuid_if_end_batch_that_started_twice_properly()
    {
        LogBatch::startBatch();
        $firstUuid = LogBatch::getUuid();

        LogBatch::startBatch();

        LogBatch::endBatch();
        LogBatch::endBatch();

        $nullUuid = LogBatch::getUuid();

        $this->assertNotNull($firstUuid);
        $this->assertNull($nullUuid);

        $this->assertNotSame($firstUuid, $nullUuid);
    }
}
