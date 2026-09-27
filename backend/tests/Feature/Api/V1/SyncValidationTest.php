<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\PdpSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\ChallengePayload;
use Tests\TestCase;

/**
 * A field device must be told precisely which record is bad, otherwise it
 * retries forever with a payload that can never succeed.
 */
final class SyncValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidRecordProvider(): array
    {
        return [
            'missing uuid' => [
                ['urban_council' => 'Mukono Municipality', 'pdp_status' => 'Active', 'field_officer_timestamp' => '2026-06-03T09:15:00Z'],
                'records.0.submission_uuid',
            ],
            'malformed uuid' => [
                ['submission_uuid' => 'not-a-uuid', 'urban_council' => 'Mukono Municipality', 'pdp_status' => 'Active', 'field_officer_timestamp' => '2026-06-03T09:15:00Z'],
                'records.0.submission_uuid',
            ],
            'missing council' => [
                ['submission_uuid' => 'f81d4fae-7dec-11d0-a765-00a0c91e6bf6', 'pdp_status' => 'Active', 'field_officer_timestamp' => '2026-06-03T09:15:00Z'],
                'records.0.urban_council',
            ],
            'unknown status' => [
                ['submission_uuid' => 'f81d4fae-7dec-11d0-a765-00a0c91e6bf6', 'urban_council' => 'Mukono Municipality', 'pdp_status' => 'Abandoned', 'field_officer_timestamp' => '2026-06-03T09:15:00Z'],
                'records.0.pdp_status',
            ],
            'missing timestamp' => [
                ['submission_uuid' => 'f81d4fae-7dec-11d0-a765-00a0c91e6bf6', 'urban_council' => 'Mukono Municipality', 'pdp_status' => 'Active'],
                'records.0.field_officer_timestamp',
            ],
            'unparseable timestamp' => [
                ['submission_uuid' => 'f81d4fae-7dec-11d0-a765-00a0c91e6bf6', 'urban_council' => 'Mukono Municipality', 'pdp_status' => 'Active', 'field_officer_timestamp' => 'yesterday'],
                'records.0.field_officer_timestamp',
            ],
            'expiry year out of range' => [
                ['submission_uuid' => 'f81d4fae-7dec-11d0-a765-00a0c91e6bf6', 'urban_council' => 'Mukono Municipality', 'pdp_status' => 'Active', 'expiry_year' => 1899, 'field_officer_timestamp' => '2026-06-03T09:15:00Z'],
                'records.0.expiry_year',
            ],
            'missing plan cannot expire' => [
                ['submission_uuid' => 'f81d4fae-7dec-11d0-a765-00a0c91e6bf6', 'urban_council' => 'Mukono Municipality', 'pdp_status' => 'Missing', 'expiry_year' => 2032, 'field_officer_timestamp' => '2026-06-03T09:15:00Z'],
                'records.0.expiry_year',
            ],
            'expiring plan needs an expiry year' => [
                ['submission_uuid' => 'f81d4fae-7dec-11d0-a765-00a0c91e6bf6', 'urban_council' => 'Mukono Municipality', 'pdp_status' => 'Expiring', 'expiry_year' => null, 'field_officer_timestamp' => '2026-06-03T09:15:00Z'],
                'records.0.expiry_year',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $record
     */
    #[DataProvider('invalidRecordProvider')]
    public function test_it_rejects_an_invalid_record(array $record, string $expectedErrorKey): void
    {
        $this->postJson('/api/v1/sync', ChallengePayload::envelope(records: [$record]))
            ->assertStatus(422)
            ->assertJsonValidationErrors($expectedErrorKey);

        $this->assertSame(0, PdpSubmission::query()->count(), 'A rejected payload must write nothing.');
    }

    public function test_it_rejects_a_payload_without_a_client_batch_id(): void
    {
        $payload = ChallengePayload::envelope();
        unset($payload['client_batch_id']);

        $this->postJson('/api/v1/sync', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('client_batch_id');
    }

    public function test_it_rejects_an_empty_queue_flush(): void
    {
        $this->postJson('/api/v1/sync', ChallengePayload::envelope(records: []))
            ->assertStatus(422)
            ->assertJsonValidationErrors('records');
    }

    public function test_it_rejects_an_oversized_flush(): void
    {
        $records = [];
        for ($i = 0; $i < 501; $i++) {
            $records[] = ChallengePayload::mukono();
        }

        $this->postJson('/api/v1/sync', ChallengePayload::envelope(records: $records))
            ->assertStatus(422)
            ->assertJsonValidationErrors('records');
    }

    public function test_it_accepts_a_null_expiry_year_for_a_missing_plan(): void
    {
        $this->postJson('/api/v1/sync', ChallengePayload::envelope(records: [ChallengePayload::gulu()]))
            ->assertOk()
            ->assertJsonPath('data.summary.committed', 1);
    }
}
