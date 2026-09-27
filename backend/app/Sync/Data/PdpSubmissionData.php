<?php

declare(strict_types=1);

namespace App\Sync\Data;

use App\Enums\PdpStatus;
use Carbon\CarbonImmutable;

/**
 * A single validated record inside an offline queue flush.
 *
 * Instances are only ever built by {@see \App\Http\Requests\Api\V1\SyncRequest},
 * so any DTO in the pipeline is guaranteed to have passed validation.
 */
final readonly class PdpSubmissionData
{
    public function __construct(
        public string $submissionUuid,
        public string $urbanCouncil,
        public PdpStatus $pdpStatus,
        public ?int $expiryYear,
        public CarbonImmutable $fieldOfficerTimestamp,
        public ?string $deviceId = null,
    ) {}

    /**
     * Business-level fingerprint. Two payloads sharing a fingerprint are the
     * same observation; a matching UUID with a different fingerprint is a
     * deliberate correction from the field.
     */
    public function fingerprint(): int
    {
        return crc32(implode('|', [
            $this->urbanCouncil,
            $this->pdpStatus->value,
            $this->expiryYear ?? 'null',
            $this->fieldOfficerTimestamp->format(\DateTimeInterface::ATOM),
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'submission_uuid' => $this->submissionUuid,
            'urban_council' => $this->urbanCouncil,
            'pdp_status' => $this->pdpStatus->value,
            'expiry_year' => $this->expiryYear,
            'field_officer_timestamp' => $this->fieldOfficerTimestamp->format(\DateTimeInterface::ATOM),
        ];
    }
}
