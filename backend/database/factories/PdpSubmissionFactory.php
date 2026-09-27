<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PdpStatus;
use App\Models\PdpSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends Factory<PdpSubmission>
 */
class PdpSubmissionFactory extends Factory
{
    protected $model = PdpSubmission::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'submission_uuid' => (string) Str::uuid(),
            'urban_council' => $this->faker->randomElement([
                'Mukono Municipality',
                'Entebbe Municipal Council',
                'Gulu City Council',
            ]),
            'pdp_status' => $this->faker->randomElement(PdpStatus::cases()),
            'expiry_year' => $this->faker->numberBetween(2026, 2040),
            'field_officer_timestamp' => Carbon::now()->subMinutes($this->faker->numberBetween(1, 600)),
            'device_id' => (string) Str::uuid(),
            'payload_hash' => null,
            'committed_at' => Carbon::now(),
        ];
    }

    public function status(PdpStatus $status): static
    {
        return $this->state(fn (): array => ['pdp_status' => $status]);
    }

    public function withoutExpiry(): static
    {
        return $this->state(fn (): array => ['expiry_year' => null]);
    }
}
