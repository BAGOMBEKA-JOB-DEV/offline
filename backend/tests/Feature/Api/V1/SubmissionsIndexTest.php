<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\PdpSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ChallengePayload;
use Tests\TestCase;

final class SubmissionsIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->postJson('/api/v1/sync', ChallengePayload::envelope())->assertOk();
    }

    public function test_it_lists_only_committed_records(): void
    {
        $this->getJson('/api/v1/submissions')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_it_filters_by_urban_council(): void
    {
        $this->getJson('/api/v1/submissions?urban_council=Mukono%20Municipality')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.urban_council', 'Mukono Municipality');
    }

    public function test_it_filters_by_pdp_status(): void
    {
        $this->getJson('/api/v1/submissions?pdp_status=Expiring')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.submission_uuid', ChallengePayload::ENTEBBE_UUID);
    }

    public function test_a_filter_matching_nothing_returns_an_empty_collection(): void
    {
        $this->getJson('/api/v1/submissions?urban_council=Nowhere')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    public function test_it_exposes_the_flush_history(): void
    {
        $this->getJson('/api/v1/sync-runs')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.outcome', 'partial')
            ->assertJsonPath('data.0.committed_count', 3)
            ->assertJsonPath('data.0.duplicate_count', 1)
            ->assertJsonPath('data.0.received_count', 4);
    }

    public function test_the_resource_exposes_a_stable_public_shape(): void
    {
        $this->getJson('/api/v1/submissions')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'submission_uuid',
                        'urban_council',
                        'pdp_status',
                        'expiry_year',
                        'field_officer_timestamp',
                        'committed_at',
                        'device_id',
                    ],
                ],
            ]);
    }

    public function test_records_are_ordered_newest_observation_first(): void
    {
        $uuids = collect($this->getJson('/api/v1/submissions')->json('data'))
            ->pluck('submission_uuid')
            ->all();

        $this->assertSame([
            ChallengePayload::GULU_UUID,
            ChallengePayload::ENTEBBE_UUID,
            ChallengePayload::MUKONO_UUID,
        ], $uuids);

        $this->assertSame(3, PdpSubmission::query()->count());
    }
}
