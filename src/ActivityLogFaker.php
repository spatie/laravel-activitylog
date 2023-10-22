<?php

namespace Spatie\Activitylog;

use Closure;
use PHPUnit\Framework\Assert as PHPUnit;

class ActivityLogFaker extends ActivityLogger
{
    protected array $loggedActivities = [];

    public function recordActivity($activity)
    {
        $this->loggedActivities[] = $activity;
    }

    public function assertLogged(int $expectedCount = null): void
    {
        $activityCount = count($this->loggedActivities);

        if ($expectedCount === null) {
            PHPUnit::assertGreaterThan(
                0,
                $activityCount,
                'No activities were logged.',
            );
        } else {
            PHPUnit::assertEquals(
                $expectedCount,
                $activityCount,
                $activityCount
                    .($activityCount === 1 ? ' activity was' : ' activities were')
                    ." logged instead of {$expectedCount} expected.",
            );
        }
    }

    public function assertNothingLogged(): void
    {
        $this->assertLogged(0);
    }

    public function assertLoggedToLog(string $logName, int $expectedCount = null): void
    {
        $activityCount = $this->loggedActivityCount(
            function ($activity) use ($logName) {
                return $activity->log_name === $logName;
            }
        );

        if ($expectedCount === null) {
            PHPUnit::assertGreaterThan(
                0,
                $activityCount,
                "No activities were logged to the log \"{$logName}\".",
            );
        } else {
            PHPUnit::assertEquals(
                $expectedCount,
                $activityCount,
                $activityCount
                    .($activityCount === 1 ? ' activity was' : ' activities were')
                    ." logged to the log \"{$logName}\" instead of {$expectedCount} expected.",
            );
        }
    }

    public function assertNothingLoggedToLog(string $logName): void
    {
        $this->assertLoggedToLog($logName, 0);
    }

    public function assertLoggedWithDescription(string $description, int $expectedCount = null): void
    {
        $activityCount = $this->loggedActivityCount(
            function ($activity) use ($description) {
                return $activity->description === $description;
            }
        );

        if ($expectedCount === null) {
            PHPUnit::assertGreaterThan(
                0,
                $activityCount,
                "No activities were logged with the description \"{$description}\".",
            );
        } else {
            PHPUnit::assertEquals(
                $expectedCount,
                $activityCount,
                $activityCount
                    .($activityCount === 1 ? ' activity was' : ' activities were')
                    ." logged with the description \"{$description}\" instead of {$expectedCount} expected.",
            );
        }
    }

    public function assertNothingLoggedWithDescription(string $description): void
    {
        $this->assertLoggedWithDescription($description, 0);
    }

    public function assertLoggedWithEvent(string $event, int $expectedCount = null): void
    {
        $activityCount = $this->loggedActivityCount(
            function ($activity) use ($event) {
                return $activity->event === $event;
            }
        );

        if ($expectedCount === null) {
            PHPUnit::assertGreaterThan(
                0,
                $activityCount,
                "No activities were logged with the event \"{$event}\".",
            );
        } else {
            PHPUnit::assertEquals(
                $expectedCount,
                $activityCount,
                $activityCount
                    .($activityCount === 1 ? ' activity was' : ' activities were')
                    ." logged with the event \"{$event}\" instead of {$expectedCount} expected.",
            );
        }
    }

    public function assertNothingLoggedWithEvent(string $event): void
    {
        $this->assertLoggedWithEvent($event, 0);
    }

    public function assertLoggedWithSubjectType(string $subjectType, int $expectedCount = null): void
    {
        $activityCount = $this->loggedActivityCount(
            function ($activity) use ($subjectType) {
                return $activity->subject_type === $subjectType;
            }
        );

        if ($expectedCount === null) {
            PHPUnit::assertGreaterThan(
                0,
                $activityCount,
                "No activities were logged on subjects with type \"{$subjectType}\".",
            );
        } else {
            PHPUnit::assertEquals(
                $expectedCount,
                $activityCount,
                $activityCount
                    .($activityCount === 1 ? ' activity was' : ' activities were')
                    ." logged on subjects with type \"{$subjectType}\" instead of {$expectedCount} expected.",
            );
        }
    }

    public function assertNothingLoggedWithSubjectType(string $subjectType): void
    {
        $this->assertLoggedWithSubjectType($subjectType, 0);
    }

    public function assertLoggedWithSubjectId(mixed $subjectId, int $expectedCount = null): void
    {
        $activityCount = $this->loggedActivityCount(
            function ($activity) use ($subjectId) {
                return $activity->subject_id === $subjectId;
            }
        );

        if ($expectedCount === null) {
            PHPUnit::assertGreaterThan(
                0,
                $activityCount,
                "No activities were logged on subjects with id \"{$subjectId}\".",
            );
        } else {
            PHPUnit::assertEquals(
                $expectedCount,
                $activityCount,
                $activityCount
                    .($activityCount === 1 ? ' activity was' : ' activities were')
                    ." logged on subjects with id \"{$subjectId}\" instead of {$expectedCount} expected.",
            );
        }
    }

    public function assertNothingLoggedWithSubjectId(mixed $subjectId): void
    {
        $this->assertLoggedWithSubjectId($subjectId, 0);
    }

    public function assertLoggedWithCauserType(string $causerType, int $expectedCount = null): void
    {
        $activityCount = $this->loggedActivityCount(
            function ($activity) use ($causerType) {
                return $activity->causer_type === $causerType;
            }
        );

        if ($expectedCount === null) {
            PHPUnit::assertGreaterThan(
                0,
                $activityCount,
                "No activities were logged for causers with type \"{$causerType}\".",
            );
        } else {
            PHPUnit::assertEquals(
                $expectedCount,
                $activityCount,
                $activityCount
                    .($activityCount === 1 ? ' activity was' : ' activities were')
                    ." logged for causers with type \"{$causerType}\" instead of {$expectedCount} expected.",
            );
        }
    }

    public function assertNothingLoggedWithCauserType(string $causerType): void
    {
        $this->assertLoggedWithCauserType($causerType, 0);
    }

    public function assertLoggedWithCauserId(mixed $causerId, int $expectedCount = null): void
    {
        $activityCount = $this->loggedActivityCount(
            function ($activity) use ($causerId) {
                return $activity->causer_id === $causerId;
            }
        );

        if ($expectedCount === null) {
            PHPUnit::assertGreaterThan(
                0,
                $activityCount,
                "No activities were logged for causers with id \"{$causerId}\".",
            );
        } else {
            PHPUnit::assertEquals(
                $expectedCount,
                $activityCount,
                $activityCount
                    .($activityCount === 1 ? ' activity was' : ' activities were')
                    ." logged for causers with id \"{$causerId}\" instead of {$expectedCount} expected.",
            );
        }
    }

    public function assertNothingLoggedWithCauserId(mixed $causerId): void
    {
        $this->assertLoggedWithCauserId($causerId, 0);
    }

    public function assertLoggedWithProperties(mixed $properties, int $expectedCount = null): void
    {
        $activityCount = $this->loggedActivityCount(
            function ($activity) use ($properties) {
                return
                    count($activity->properties) === count($properties)
                    && count($activity->properties->intersect($properties)) === count($properties);
            }
        );

        if ($expectedCount === null) {
            PHPUnit::assertGreaterThan(
                0,
                $activityCount,
                'No activities were logged with the specified properties.',
            );
        } else {
            PHPUnit::assertEquals(
                $expectedCount,
                $activityCount,
                $activityCount
                    .($activityCount === 1 ? ' activity was' : ' activities were')
                    ." logged with the specified properties, instead of {$expectedCount} expected.",
            );
        }
    }

    public function assertNothingLoggedWithProperties(mixed $properties): void
    {
        $this->assertLoggedWithProperties($properties, 0);
    }

    public function assertLoggedIncludingProperties(mixed $properties, int $expectedCount = null): void
    {
        $activityCount = $this->loggedActivityCount(
            function ($activity) use ($properties) {
                return count($activity->properties->intersect($properties)) === count($properties);
            }
        );

        if ($expectedCount === null) {
            PHPUnit::assertGreaterThan(
                0,
                $activityCount,
                'No activities were logged with the specified properties.',
            );
        } else {
            PHPUnit::assertEquals(
                $expectedCount,
                $activityCount,
                $activityCount
                    .($activityCount === 1 ? ' activity was' : ' activities were')
                    ." logged that included the specified properties, instead of {$expectedCount} expected.",
            );
        }
    }

    public function assertNothingLoggedIncludingProperties(mixed $properties): void
    {
        $this->assertLoggedIncludingProperties($properties, 0);
    }

    public function assertLoggedMatching(Closure $callback, int $expectedCount = null): void
    {
        $activityCount = $this->loggedActivityCount($callback);

        if ($expectedCount === null) {
            PHPUnit::assertGreaterThan(
                0,
                $activityCount,
                'No activities were logged that matched the specified criteria.',
            );
        } else {
            PHPUnit::assertEquals(
                $expectedCount,
                $activityCount,
                $activityCount
                    .($activityCount === 1 ? ' activity was' : ' activities were')
                    ." logged that matched the specified criteria, instead of {$expectedCount} expected.",
            );
        }
    }

    public function assertNothingLoggedMatching(Closure $callback): void
    {
        $this->assertLoggedMatching($callback, 0);
    }

    private function loggedActivityCount(Closure $callback): int
    {
        return count(
            collect($this->loggedActivities)->filter($callback)
        );
    }
}
