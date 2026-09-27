<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PdpStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single Physical Development Plan observation captured in the field.
 *
 * The primary key is the client-supplied `submission_uuid`, which makes every
 * write path naturally idempotent: replaying a payload cannot create a second
 * row, no matter how many times the device retries.
 *
 * @property string $submission_uuid
 * @property string $urban_council
 * @property PdpStatus $pdp_status
 * @property int|null $expiry_year
 * @property \Carbon\CarbonImmutable $field_officer_timestamp
 * @property string|null $device_id
 * @property int|null $payload_hash
 * @property \Carbon\CarbonImmutable|null $committed_at
 * @property int|null $ingest_sequence
 */
class PdpSubmission extends Model
{
    /** @use HasFactory<\Database\Factories\PdpSubmissionFactory> */
    use HasFactory;

    protected $table = 'pdp_submissions';

    protected $primaryKey = 'submission_uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /**
     * `expiry_year` is deliberately *not* cast to a year type: the domain only
     * ever needs a 4-digit integer, and keeping it an int avoids Carbon's
     * platform-dependent year handling on a value this small.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pdp_status' => PdpStatus::class,
            'expiry_year' => 'integer',
            'field_officer_timestamp' => 'immutable_datetime',
            'committed_at' => 'immutable_datetime',
            'payload_hash' => 'integer',
            'ingest_sequence' => 'integer',
        ];
    }

    /**
     * Stable, order-independent fingerprint of the business payload.
     *
     * Used to distinguish a genuine re-submission of the same UUID with changed
     * data (a correction) from a byte-identical replay.
     */
    public function fingerprint(): int
    {
        return crc32(implode('|', [
            $this->urban_council,
            $this->pdp_status->value,
            $this->expiry_year ?? 'null',
            $this->field_officer_timestamp->toIso8601String(),
        ]));
    }

    /**
     * @param  Builder<PdpSubmission>  $query
     * @return Builder<PdpSubmission>
     */
    public function scopeActionable(Builder $query): Builder
    {
        return $query->whereIn('pdp_status', [
            PdpStatus::Active->value,
            PdpStatus::Expiring->value,
        ]);
    }

    /**
     * @param  Builder<PdpSubmission>  $query
     * @return Builder<PdpSubmission>
     */
    public function scopeForCouncil(Builder $query, string $council): Builder
    {
        return $query->where('urban_council', $council);
    }

    /**
     * Human-readable label, kept out of the attribute accessor layer on purpose
     * so the API resource controls presentation.
     */
    protected function label(): Attribute
    {
        return Attribute::get(fn (): string => sprintf(
            '%s — %s%s',
            $this->urban_council,
            $this->pdp_status->value,
            $this->expiry_year !== null ? ' (expiry '.$this->expiry_year.')' : '',
        ));
    }

    /**
     * The sync run that first committed this record, if tracked.
     */
    public function syncRun(): BelongsTo
    {
        return $this->belongsTo(SyncRun::class, 'sync_run_id');
    }
}
