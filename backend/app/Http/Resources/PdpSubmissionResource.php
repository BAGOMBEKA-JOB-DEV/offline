<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\PdpSubmission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PdpSubmission
 */
final class PdpSubmissionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'submission_uuid' => $this->submission_uuid,
            'urban_council' => $this->urban_council,
            'pdp_status' => $this->pdp_status->value,
            'expiry_year' => $this->expiry_year,
            'field_officer_timestamp' => $this->field_officer_timestamp->format(\DateTimeInterface::ATOM),
            'committed_at' => $this->committed_at?->format(\DateTimeInterface::ATOM),
            'device_id' => $this->device_id,
        ];
    }
}
