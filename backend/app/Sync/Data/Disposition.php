<?php

declare(strict_types=1);

namespace App\Sync\Data;

/**
 * Per-record verdict returned to the device, so the field officer's client can
 * drop the item from its local queue with confidence.
 */
final readonly class Disposition
{
    public function __construct(
        public string $submissionUuid,
        public DispositionReason $reason,
        public bool $committed,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'submission_uuid' => $this->submissionUuid,
            'committed' => $this->committed,
            'reason' => $this->reason->value,
        ];
    }
}
