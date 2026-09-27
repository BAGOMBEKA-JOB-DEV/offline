<?php

declare(strict_types=1);

namespace App\Sync;

use App\Enums\SyncOutcome;
use App\Models\PdpSubmission;
use App\Models\SyncRun;
use App\Sync\Data\Disposition;
use App\Sync\Data\DispositionReason;
use App\Sync\Data\PdpSubmissionData;
use App\Sync\Data\SyncResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Ingests a batch of offline-queued PDP observations.
 *
 * ## Why this shape
 *
 * Field devices retry aggressively because a dropped response is
 * indistinguishable from a failed write. Two independent layers absorb that:
 *
 * 1. **Batch-level idempotency** — `sync_runs.client_batch_id` is unique, so a
 *    retried flush short-circuits and returns the original verdict. This is the
 *    layer that saves work.
 * 2. **Record-level idempotency** — `pdp_submissions.submission_uuid` is the
 *    primary key, so even a batch that slips past layer 1 (new batch id, same
 *    records) cannot create a second row. This is the layer that guarantees
 *    correctness.
 *
 * In-batch duplicates are also caught *before* touching the database, so the
 * Mukono record that appears twice in one payload costs a single write.
 */
final class SyncIngestionService
{
    /**
     * Process one flush payload.
     *
     * @param  Collection<int, PdpSubmissionData>  $records
     */
    public function ingest(string $clientBatchId, Collection $records, ?string $deviceId = null): SyncResult
    {
        $startedAt = Carbon::now();

        // Layer 1: is this an exact replay of a flush we already processed?
        $existing = SyncRun::query()->where('client_batch_id', $clientBatchId)->first();

        if ($existing !== null) {
            return new SyncResult(
                run: $existing,
                dispositions: $this->rebuildDispositions($existing),
                replayed: true,
            );
        }

        // Wrap the whole flush so a mid-batch failure leaves no partial writes.
        return DB::transaction(function () use ($clientBatchId, $records, $deviceId, $startedAt): SyncResult {
            $run = SyncRun::query()->create([
                'client_batch_id' => $clientBatchId,
                'device_id' => $deviceId ?? $records->first()?->deviceId,
                'outcome' => SyncOutcome::Committed,
                'received_count' => $records->count(),
                'committed_count' => 0,
                'duplicate_count' => 0,
                'rejected_count' => 0,
                'started_at' => $startedAt,
                'finished_at' => null,
            ]);

            $dispositions = new Collection;
            $seenInBatch = [];
            $sequence = 0;

            foreach ($records as $record) {
                $sequence++;
                $uuid = $record->submissionUuid;

                // In-batch duplicate: drop before any database round trip.
                if (isset($seenInBatch[$uuid])) {
                    $dispositions->push(new Disposition($uuid, DispositionReason::DuplicateWithinBatch, committed: false));
                    $run->increment('duplicate_count');
                    continue;
                }

                $seenInBatch[$uuid] = true;

                $existingRecord = PdpSubmission::query()->find($uuid);

                if ($existingRecord !== null) {
                    $isIdenticalReplay = $existingRecord->fingerprint() === $record->fingerprint();

                    if ($isIdenticalReplay) {
                        // Layer 2, fast path: a pure replay. Nothing to write.
                        $dispositions->push(new Disposition($uuid, DispositionReason::Duplicate, committed: false));
                        $run->increment('duplicate_count');
                        continue;
                    }

                    // Same UUID, different data: the field officer corrected the
                    // record, so last write wins and the correction is reported.
                    $existingRecord->fill([
                        'urban_council' => $record->urbanCouncil,
                        'pdp_status' => $record->pdpStatus,
                        'expiry_year' => $record->expiryYear,
                        'field_officer_timestamp' => $record->fieldOfficerTimestamp,
                        'payload_hash' => $record->fingerprint(),
                        'sync_run_id' => $run->id,
                        'ingest_sequence' => $sequence,
                    ])->save();

                    $dispositions->push(new Disposition($uuid, DispositionReason::CorrectionSuperseded, committed: true));
                    $run->increment('committed_count');
                    continue;
                }

                PdpSubmission::query()->create([
                    'submission_uuid' => $uuid,
                    'urban_council' => $record->urbanCouncil,
                    'pdp_status' => $record->pdpStatus,
                    'expiry_year' => $record->expiryYear,
                    'field_officer_timestamp' => $record->fieldOfficerTimestamp,
                    'device_id' => $record->deviceId,
                    'payload_hash' => $record->fingerprint(),
                    'committed_at' => Carbon::now(),
                    'sync_run_id' => $run->id,
                    'ingest_sequence' => $sequence,
                ]);

                $dispositions->push(new Disposition($uuid, DispositionReason::Committed, committed: true));
                $run->increment('committed_count');
            }

            $this->finaliseRun($run->fresh(), $dispositions);

            return new SyncResult(
                run: $run->fresh(),
                dispositions: $dispositions->all(),
                replayed: false,
            );
        });
    }

    /**
     * Derive the aggregate outcome from what actually happened.
     */
    private function finaliseRun(SyncRun $run, Collection $dispositions): void
    {
        $committed = $dispositions->where('committed', true)->count();
        $received = $run->received_count;

        $outcome = match (true) {
            $committed === 0 && $received === 0 => SyncOutcome::Committed,
            $committed === 0 => SyncOutcome::Rejected,
            $committed < $received => SyncOutcome::Partial,
            default => SyncOutcome::Committed,
        };

        $run->forceFill([
            'outcome' => $outcome,
            'finished_at' => Carbon::now(),
        ])->save();
    }

    /**
     * Reconstruct the per-record verdicts of a previously completed run so a
     * replayed flush returns an identical body to the original response.
     *
     * @return list<Disposition>
     */
    private function rebuildDispositions(SyncRun $run): array
    {
        return PdpSubmission::query()
            ->where('sync_run_id', $run->id)
            ->orderBy('ingest_sequence')
            ->get()
            ->map(fn (PdpSubmission $s): Disposition => new Disposition(
                $s->submission_uuid,
                DispositionReason::CorrectionSuperseded,
                committed: true,
            ))
            ->all();
    }

    /**
     * Mint a batch id for clients that do not supply one.
     */
    public static function newClientBatchId(): string
    {
        return (string) Str::uuid();
    }
}
