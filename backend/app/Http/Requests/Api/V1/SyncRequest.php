<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\PdpStatus;
use App\Rules\ExpiryMatchesPdpStatus;
use App\Sync\Data\PdpSubmissionData;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates one flush of an offline queue.
 *
 * Validation is deliberately *per record* rather than fail-fast on the batch:
 * a field device can hold a corrupt entry, and the correct behaviour is to
 * reject that entry and still commit the healthy ones. `bail` stops at the
 * first failure per record so a device gets one actionable error per bad row
 * instead of a cascade.
 */
final class SyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'client_batch_id' => ['required', 'uuid'],

            'device_id' => ['nullable', 'uuid'],

            'records' => ['present', 'array', 'min:1', 'max:500'],

            'records.*.submission_uuid' => ['bail', 'required', 'uuid'],
            'records.*.urban_council' => ['bail', 'required', 'string', 'min:2', 'max:255'],
            'records.*.pdp_status' => ['bail', 'required', 'string', Rule::in(PdpStatus::values())],
            'records.*.expiry_year' => ['bail', 'nullable', 'integer', 'min:2000', 'max:2100'],
            // ISO-8601 with either a `Z` suffix or a numeric offset. Laravel's
            // `date_format` accepts one format only, and the brief's payload
            // uses `Z`, so the shape is pinned with a regex and the value is
            // then confirmed to be a real instant.
            'records.*.field_officer_timestamp' => [
                'bail',
                'required',
                'string',
                'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(Z|[+-]\d{2}:\d{2})$/',
                'date',
            ],

            // Cross-field rule: an expiring year is meaningless on a plan that
            // has no expiry, and a missing plan cannot be "Expiring".
            'records.*' => [new ExpiryMatchesPdpStatus],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'records.*.expiry_year.integer' => 'The expiry year must be a whole number.',
            'records.*.pdp_status.in' => 'The PDP status must be one of: '.implode(', ', PdpStatus::values()).'.',
        ];
    }

    /**
     * Project the validated payload into typed DTOs for the service layer.
     *
     * @return array{client_batch_id: string, device_id: ?string, records: \Illuminate\Support\Collection<int, PdpSubmissionData>}
     */
    public function toSyncPayload(): array
    {
        return [
            'client_batch_id' => $this->string('client_batch_id')->toString(),
            'device_id' => $this->input('device_id') !== null
                ? $this->string('device_id')->toString()
                : null,
            'records' => collect($this->validated('records'))->map(
                fn (array $record): PdpSubmissionData => new PdpSubmissionData(
                    submissionUuid: $record['submission_uuid'],
                    urbanCouncil: $record['urban_council'],
                    pdpStatus: PdpStatus::from($record['pdp_status']),
                    expiryYear: $record['expiry_year'] ?? null,
                    fieldOfficerTimestamp: CarbonImmutable::parse($record['field_officer_timestamp']),
                    deviceId: $this->input('device_id'),
                )
            ),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'records' => 'records',
            'records.*.submission_uuid' => 'submission uuid',
            'records.*.urban_council' => 'urban council',
            'records.*.pdp_status' => 'PDP status',
            'records.*.expiry_year' => 'expiry year',
            'records.*.field_officer_timestamp' => 'field officer timestamp',
        ];
    }
}
