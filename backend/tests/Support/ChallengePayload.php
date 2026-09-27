<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * The exact queued payload from the challenge brief, plus the variant builders
 * used to prove each guard rail actually holds.
 */
final class ChallengePayload
{
    public const MUKONO_UUID = 'f81d4fae-7dec-11d0-a765-00a0c91e6bf6';

    public const ENTEBBE_UUID = '6ec0bd7f-11c0-43da-975e-2a8ad9ebae0b';

    public const GULU_UUID = 'bc29e1a8-89c0-4fb1-b12e-1b32d20912ab';

    /**
     * The verbatim four-record payload: three unique records plus one
     * byte-identical Mukono replay.
     *
     * @return list<array<string, mixed>>
     */
    public static function records(): array
    {
        return [
            self::mukono(),
            self::entebbe(),
            self::mukono(),
            self::gulu(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function mukono(): array
    {
        return [
            'submission_uuid' => self::MUKONO_UUID,
            'urban_council' => 'Mukono Municipality',
            'pdp_status' => 'Active',
            'expiry_year' => 2032,
            'field_officer_timestamp' => '2026-06-03T09:15:00Z',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function entebbe(): array
    {
        return [
            'submission_uuid' => self::ENTEBBE_UUID,
            'urban_council' => 'Entebbe Municipal Council',
            'pdp_status' => 'Expiring',
            'expiry_year' => 2026,
            'field_officer_timestamp' => '2026-06-03T10:22:11Z',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function gulu(): array
    {
        return [
            'submission_uuid' => self::GULU_UUID,
            'urban_council' => 'Gulu City Council',
            'pdp_status' => 'Missing',
            'expiry_year' => null,
            'field_officer_timestamp' => '2026-06-03T11:05:45Z',
        ];
    }

    /**
     * Wrap records in the flush envelope.
     *
     * @param  list<array<string, mixed>>|null  $records
     * @return array<string, mixed>
     */
    public static function envelope(?string $batchId = null, ?array $records = null): array
    {
        return [
            'client_batch_id' => $batchId ?? '3f2504e0-4f89-41d3-9a0c-0305e82c3301',
            'device_id' => '9c858901-8a57-4791-81fe-4c455b099bc9',
            'records' => $records ?? self::records(),
        ];
    }
}
