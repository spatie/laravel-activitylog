<?php

namespace Spatie\Activitylog;

use Closure;
use Ramsey\Uuid\Uuid;

class LogBatch
{
    public ?string $uuid = null;

    public int $transactions = 0;

    protected function generateUuid(): string
    {
        return Uuid::uuid4()->toString();
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setBatch(string $uuid): void
    {
        $this->uuid = $uuid;
        $this->transactions = 1;
    }

    public function withinBatch(Closure $callback): mixed
    {
        $this->startBatch();
        $result = $callback($this->getUuid());
        $this->endBatch();

        return $result;
    }

    public function startBatch(): void
    {
        if (! $this->isOpen()) {
            $this->uuid = $this->generateUuid();
        }

        $this->transactions++;
    }

    public function isOpen(): bool
    {
        return $this->transactions > 0;
    }

    public function endBatch(): void
    {
        $this->transactions = max(0, $this->transactions - 1);

        if ($this->transactions === 0) {
            $this->uuid = null;
        }
    }
}
