<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\PdpStatus;
use App\Sync\Data\PdpSubmissionData;
use App\Sync\SyncIngestionService;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

final class PdpSubmissionDataTest extends TestCase
{
    public function test_the_fingerprint_is_stable_across_equal_payloads(): void
    {
        $a = $this->data(submissionUuid: 'a', status: PdpStatus::Active, year: 2032);
        $b = $this->data(submissionUuid: 'b', status: PdpStatus::Active, year: 2032);

        // Fingerprint covers business data only: the same observation minted on
        // two devices must hash identically, or every replay would look like a
        // correction.
        $this->assertSame($a->fingerprint(), $b->fingerprint());
    }

    public function test_the_fingerprint_changes_when_business_data_changes(): void
    {
        $baseline = $this->data(status: PdpStatus::Active, year: 2032);

        $this->assertNotSame($baseline->fingerprint(), $this->data(status: PdpStatus::Suspended, year: 2032)->fingerprint());
        $this->assertNotSame($baseline->fingerprint(), $this->data(status: PdpStatus::Active, year: 2033)->fingerprint());
        $this->assertNotSame($baseline->fingerprint(), $this->data(status: PdpStatus::Active, year: null)->fingerprint());
    }

    public function test_a_null_expiry_year_is_distinct_from_a_zero_expiry_year(): void
    {
        $this->assertNotSame(
            $this->data(year: null)->fingerprint(),
            $this->data(year: 0)->fingerprint(),
        );
    }

    public function test_it_round_trips_to_a_canonical_array(): void
    {
        $data = $this->data();

        $this->assertSame([
            'submission_uuid' => 'f81d4fae-7dec-11d0-a765-00a0c91e6bf6',
            'urban_council' => 'Mukono Municipality',
            'pdp_status' => 'Active',
            'expiry_year' => 2032,
            'field_officer_timestamp' => '2026-06-03T09:15:00+00:00',
        ], $data->toArray());
    }

    public function test_it_mints_a_well_formed_client_batch_id(): void
    {
        $this->assertTrue(
            \Illuminate\Support\Str::isUuid(SyncIngestionService::newClientBatchId()),
        );
    }

    private function data(
        string $submissionUuid = 'f81d4fae-7dec-11d0-a765-00a0c91e6bf6',
        PdpStatus $status = PdpStatus::Active,
        ?int $year = 2032,
    ): PdpSubmissionData {
        return new PdpSubmissionData(
            submissionUuid: $submissionUuid,
            urbanCouncil: 'Mukono Municipality',
            pdpStatus: $status,
            expiryYear: $year,
            fieldOfficerTimestamp: CarbonImmutable::parse('2026-06-03T09:15:00Z'),
        );
    }
}
