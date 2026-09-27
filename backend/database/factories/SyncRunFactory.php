<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SyncOutcome;
use App\Models\SyncRun;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SyncRun>
 */
class SyncRunFactory extends Factory
{
    protected $model = SyncRun::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_batch_id' => (string) Str::uuid(),
            'device_id' => (string) Str::uuid(),
            'outcome' => SyncOutcome::Committed,
            'received_count' => 0,
            'committed_count' => 0,
            'duplicate_count' => 0,
            'rejected_count' => 0,
            'started_at' => now(),
            'finished_at' => now(),
        ];
    }
}
