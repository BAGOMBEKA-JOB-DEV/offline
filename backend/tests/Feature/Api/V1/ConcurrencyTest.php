<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\PdpSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ChallengePayload;
use Tests\TestCase;

/**
 * Concurrent flushes from two devices are the classic double-commit race. The
 * primary key must collapse them, not the application layer.
 */
final class ConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_primary_key_prevents_a_duplicate_row_under_a_racing_flush(): void
    {
        $this->postJson('/api/v1/sync', ChallengePayload::envelope())->assertOk();

        $this->assertSame(3, PdpSubmission::query()->count());

        // Simulate a device that crashed before receiving the response and
        // replays the identical payload under a fresh batch id.
        $replay = $this->postJson('/api/v1/sync', ChallengePayload::envelope(
            batchId: '44444444-4444-4444-8444-444444444444',
        ));

        $replay->assertOk();

        $this->assertSame(3, PdpSubmission::query()->count());
        $this->assertSame(3, PdpSubmission::query()->distinct()->count('submission_uuid'));
    }

    public function test_a_duplicate_insert_would_violate_the_primary_key(): void
    {
        PdpSubmission::query()->create(ChallengePayload::mukono() + [
            'pdp_status' => 'Active',
            'field_officer_timestamp' => '2026-06-03T09:15:00Z',
        ]);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        PdpSubmission::query()->create(ChallengePayload::mukono() + [
            'pdp_status' => 'Active',
            'field_officer_timestamp' => '2026-06-03T09:15:00Z',
        ]);
    }
}
