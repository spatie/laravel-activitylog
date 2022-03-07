<?php

use Spatie\Activitylog\Facades\LogBatch;
use Illuminate\Support\Str;

uses(TestCase::class);

it('generates uuid after start and end batch properely', function () {
    LogBatch::startBatch();
    $uuid = LogBatch::getUuid();
    LogBatch::endBatch();

    $this->assertFalse(LogBatch::isopen());

    $this->assertIsString($uuid);
});

it('returns null uuid after end batch properely', function () {
    LogBatch::startBatch();
    $uuid = LogBatch::getUuid();
    LogBatch::endBatch();

    $this->assertFalse(LogBatch::isopen());
    $this->assertNotNull($uuid);
    $this->assertNull(LogBatch::getUuid());
});

it('generates a new uuid after starting new batch properly', function () {
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
});

it('will not generate new uuid if start already started batch', function () {
    LogBatch::startBatch();

    $firstUuid = LogBatch::getUuid();

    LogBatch::startBatch();

    $secondUuid = LogBatch::getUuid();

    LogBatch::endBatch();

    $this->assertTrue(LogBatch::isopen());

    $this->assertEquals($firstUuid, $secondUuid);
});

it('will not generate uuid if end batch before starting', function () {
    LogBatch::endBatch();
    $uuid = LogBatch::getUuid();

    LogBatch::startBatch();

    $this->assertNull($uuid);
});

it('can set uuid and start a batch', function () {
    $uuid = Str::uuid();

    LogBatch::setBatch($uuid);
    $this->assertTrue(LogBatch::isOpen());
    $this->assertEquals($uuid, LogBatch::getUuid());

    LogBatch::endBatch();
    $this->assertFalse(LogBatch::isOpen());
});

it('can set uuid for already started batch', function () {
    $uuid = Str::uuid();

    LogBatch::startBatch();
    $this->assertTrue(LogBatch::isOpen());
    $this->assertNotEquals($uuid, LogBatch::getUuid());

    LogBatch::setBatch($uuid);
    $this->assertTrue(LogBatch::isOpen());
    $this->assertEquals($uuid, LogBatch::getUuid());

    LogBatch::endBatch();
    $this->assertFalse(LogBatch::isOpen());
});

it('will not return null uuid if end batch that started twice', function () {
    LogBatch::startBatch();
    $firstUuid = LogBatch::getUuid();

    LogBatch::startBatch();

    LogBatch::endBatch();

    $notNullUuid = LogBatch::getUuid();

    $this->assertNotNull($firstUuid);
    $this->assertNotNull($notNullUuid);

    $this->assertSame($firstUuid, $notNullUuid);
});

it('will return null uuid if end batch that started twice properly', function () {
    LogBatch::startBatch();
    $firstUuid = LogBatch::getUuid();

    LogBatch::startBatch();

    LogBatch::endBatch();
    LogBatch::endBatch();

    $nullUuid = LogBatch::getUuid();

    $this->assertNotNull($firstUuid);
    $this->assertNull($nullUuid);

    $this->assertNotSame($firstUuid, $nullUuid);
});
