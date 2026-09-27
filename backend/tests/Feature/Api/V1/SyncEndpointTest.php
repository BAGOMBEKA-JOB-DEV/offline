<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\PdpSubmission;
use App\Models\SyncRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ChallengePayload;
use Tests\TestCase;

/**
 * The three behaviours the challenge brief asks for, asserted end to end
 * through the HTTP boundary a field device would actually use.
 */
final class SyncEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_commits_only_the_three_unique_records_from_the_queued_payload(): void
    {
        $response = $this->postJson('/api/v1/sync', ChallengePayload::envelope());

        $response->assertOk()
            ->assertJsonPath('data.summary.received', 4)
            ->assertJsonPath('data.summary.committed', 3)
            ->assertJsonPath('data.summary.dropped_as_duplicate', 1)
            ->assertJsonPath('data.summary.replayed', false)
            ->assertJsonPath('data.summary.outcome', 'partial')
            ->assertJsonCount(3, 'data.committed');

        $this->assertSame(3, PdpSubmission::query()->count());
    }

    public function test_it_intercepts_and_drops_the_duplicate_mukono_submission(): void
    {
        $response = $this->postJson('/api/v1/sync', ChallengePayload::envelope());

        $duplicates = collect($response->json('data.dispositions'))
            ->where('committed', false)
            ->values()
            ->all();

        $this->assertCount(1, $duplicates);
        $this->assertSame(ChallengePayload::MUKONO_UUID, $duplicates[0]['submission_uuid']);
        $this->assertSame('duplicate_within_batch', $duplicates[0]['reason']);

        // The duplicate must not have produced a second row.
        $this->assertSame(
            1,
            PdpSubmission::query()->where('submission_uuid', ChallengePayload::MUKONO_UUID)->count(),
        );
    }

    public function test_the_committed_records_match_the_expected_three(): void
    {
        $this->postJson('/api/v1/sync', ChallengePayload::envelope())->assertOk();

        $committed = PdpSubmission::query()->orderBy('ingest_sequence')->get();

        $this->assertSame(
            [
                ChallengePayload::MUKONO_UUID,
                ChallengePayload::ENTEBBE_UUID,
                ChallengePayload::GULU_UUID,
            ],
            $committed->pluck('submission_uuid')->all(),
        );

        $mukono = $committed->firstWhere('submission_uuid', ChallengePayload::MUKONO_UUID);
        $this->assertSame('Mukono Municipality', $mukono->urban_council);
        $this->assertSame('Active', $mukono->pdp_status->value);
        $this->assertSame(2032, $mukono->expiry_year);
        $this->assertSame('2026-06-03T09:15:00+00:00', $mukono->field_officer_timestamp->format(\DateTimeInterface::ATOM));

        $gulu = $committed->firstWhere('submission_uuid', ChallengePayload::GULU_UUID);
        $this->assertNull($gulu->expiry_year, 'A Missing plan must persist a null expiry year.');
    }

    public function test_replaying_the_same_batch_id_is_absorbed_without_rewriting(): void
    {
        $this->postJson('/api/v1/sync', ChallengePayload::envelope())->assertOk();

        $replay = $this->postJson('/api/v1/sync', ChallengePayload::envelope());

        $replay->assertOk()
            ->assertJsonPath('data.summary.replayed', true);

        $this->assertSame(3, PdpSubmission::query()->count());
        $this->assertSame(1, SyncRun::query()->count(), 'A replay must not create a second sync run.');
    }

    public function test_the_same_records_under_a_new_batch_id_are_still_deduplicated(): void
    {
        $this->postJson('/api/v1/sync', ChallengePayload::envelope(batchId: '11111111-1111-4111-8111-111111111111'))->assertOk();

        // A device that re-mints its batch id must still not create rows.
        $second = $this->postJson('/api/v1/sync', ChallengePayload::envelope(batchId: '22222222-2222-4222-8222-222222222222'));

        // A device that re-mints its batch id still drops all four entries: the
        // three known UUIDs are cross-batch duplicates, and the repeated Mukono
        // record is a fourth in-batch duplicate.
        $second->assertOk()
            ->assertJsonPath('data.summary.committed', 0)
            ->assertJsonPath('data.summary.dropped_as_duplicate', 4)
            ->assertJsonPath('data.summary.outcome', 'rejected');

        $this->assertSame(3, PdpSubmission::query()->count());
        $this->assertSame(2, SyncRun::query()->count());
    }

    public function test_a_correction_with_the_same_uuid_overwrites_the_stored_record(): void
    {
        $this->postJson('/api/v1/sync', ChallengePayload::envelope())->assertOk();

        $corrected = ChallengePayload::mukono();
        $corrected['pdp_status'] = 'Suspended';
        $corrected['expiry_year'] = null;

        $response = $this->postJson('/api/v1/sync', ChallengePayload::envelope(
            batchId: '33333333-3333-4333-8333-333333333333',
            records: [$corrected],
        ));

        $response->assertOk()
            ->assertJsonPath('data.summary.committed', 1)
            ->assertJsonPath('data.dispositions.0.reason', 'correction_superseded');

        $stored = PdpSubmission::query()->where('submission_uuid', ChallengePayload::MUKONO_UUID)->firstOrFail();

        $this->assertSame('Suspended', $stored->pdp_status->value);
        $this->assertNull($stored->expiry_year);
        $this->assertSame(3, PdpSubmission::query()->count(), 'A correction must not add a row.');
    }

    public function test_the_entire_payload_commits_when_it_contains_no_duplicates(): void
    {
        $response = $this->postJson('/api/v1/sync', ChallengePayload::envelope(
            records: [ChallengePayload::mukono(), ChallengePayload::entebbe(), ChallengePayload::gulu()],
        ));

        $response->assertOk()
            ->assertJsonPath('data.summary.received', 3)
            ->assertJsonPath('data.summary.committed', 3)
            ->assertJsonPath('data.summary.dropped_as_duplicate', 0)
            ->assertJsonPath('data.summary.outcome', 'committed');

        $this->assertSame(3, PdpSubmission::query()->count());
    }
}
