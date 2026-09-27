<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SyncOutcome;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Audit record for one flush of an offline queue.
 *
 * `client_batch_id` is unique so that a device which loses the HTTP response
 * and retries the same flush gets the original result back instead of
 * double-processing it.
 *
 * @property int $id
 * @property string $client_batch_id
 * @property string|null $device_id
 * @property SyncOutcome $outcome
 * @property int $received_count
 * @property int $committed_count
 * @property int $duplicate_count
 * @property int $rejected_count
 * @property \Carbon\CarbonImmutable|null $started_at
 * @property \Carbon\CarbonImmutable|null $finished_at
 */
class SyncRun extends Model
{
    /** @use HasFactory<\Database\Factories\SyncRunFactory> */
    use HasFactory;

    protected $table = 'sync_runs';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'outcome' => SyncOutcome::class,
            'received_count' => 'integer',
            'committed_count' => 'integer',
            'duplicate_count' => 'integer',
            'rejected_count' => 'integer',
            'started_at' => 'immutable_datetime',
            'finished_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return HasMany<PdpSubmission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(PdpSubmission::class);
    }

    /**
     * Milliseconds spent processing the flush, for the operations dashboard.
     */
    protected function durationMs(): Attribute
    {
        return Attribute::get(function (): ?int {
            if ($this->started_at === null || $this->finished_at === null) {
                return null;
            }

            return (int) round(($this->finished_at->getTimestampMs() - $this->started_at->getTimestampMs()));
        });
    }
}
