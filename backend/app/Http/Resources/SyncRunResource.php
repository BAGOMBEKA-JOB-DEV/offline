<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\SyncRun;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SyncRun
 */
final class SyncRunResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_batch_id' => $this->client_batch_id,
            'device_id' => $this->device_id,
            'outcome' => $this->outcome->value,
            'received_count' => $this->received_count,
            'committed_count' => $this->committed_count,
            'duplicate_count' => $this->duplicate_count,
            'rejected_count' => $this->rejected_count,
            'duration_ms' => $this->duration_ms,
            'started_at' => $this->started_at?->format(\DateTimeInterface::ATOM),
            'finished_at' => $this->finished_at?->format(\DateTimeInterface::ATOM),
        ];
    }
}
