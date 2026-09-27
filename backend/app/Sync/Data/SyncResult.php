<?php

declare(strict_types=1);

namespace App\Sync\Data;

use App\Enums\SyncOutcome;
use App\Models\SyncRun;

/**
 * Aggregate result of one queue flush.
 */
final readonly class SyncResult
{
    /**
     * @param  list<Disposition>  $dispositions
     */
    public function __construct(
        public SyncRun $run,
        public array $dispositions,
        public bool $replayed,
    ) {}

    public function committedCount(): int
    {
        return $this->run->committed_count;
    }

    public function duplicateCount(): int
    {
        return $this->run->duplicate_count;
    }

    public function rejectedCount(): int
    {
        return $this->run->rejected_count;
    }

    public function receivedCount(): int
    {
        return $this->run->received_count;
    }

    /**
     * @return list<string>
     */
    public function committedUuids(): array
    {
        return array_values(array_map(
            static fn (Disposition $d): string => $d->submissionUuid,
            array_filter($this->dispositions, static fn (Disposition $d): bool => $d->committed),
        ));
    }

    /**
     * @return list<string>
     */
    public function duplicateUuids(): array
    {
        return array_values(array_map(
            static fn (Disposition $d): string => $d->submissionUuid,
            array_filter(
                $this->dispositions,
                static fn (Disposition $d): bool => ! $d->committed
                    && in_array($d->reason, [DispositionReason::Duplicate, DispositionReason::DuplicateWithinBatch], true),
            ),
        ));
    }

    public function outcome(): SyncOutcome
    {
        return $this->run->outcome;
    }
}
