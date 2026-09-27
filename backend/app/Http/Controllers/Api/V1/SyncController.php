<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SyncRequest;
use App\Http\Resources\PdpSubmissionResource;
use App\Http\Resources\SyncRunResource;
use App\Models\PdpSubmission;
use App\Models\SyncRun;
use App\Sync\SyncIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SyncController extends Controller
{
    public function __construct(
        private readonly SyncIngestionService $ingestion,
    ) {}

    /**
     * Flush a device's offline queue.
     *
     * Always 200 on success, including replays: a replay is a normal outcome,
     * not an error, and the device needs the same body to clear its queue.
     */
    public function store(SyncRequest $request): JsonResponse
    {
        $payload = $request->toSyncPayload();

        $result = $this->ingestion->ingest(
            clientBatchId: $payload['client_batch_id'],
            records: $payload['records'],
            deviceId: $payload['device_id'],
        );

        $committed = PdpSubmission::query()
            ->whereIn('submission_uuid', $result->committedUuids())
            ->orderBy('ingest_sequence')
            ->get();

        return response()->json([
            'data' => [
                'run' => (new SyncRunResource($result->run))->resolve($request),
                'summary' => [
                    'received' => $result->receivedCount(),
                    'committed' => $result->committedCount(),
                    'dropped_as_duplicate' => $result->duplicateCount(),
                    'replayed' => $result->replayed,
                    'outcome' => $result->outcome()->value,
                ],
                'dispositions' => array_map(
                    static fn ($disposition): array => $disposition->toArray(),
                    $result->dispositions,
                ),
                'committed' => PdpSubmissionResource::collection($committed)->resolve($request),
            ],
        ]);
    }

    /**
     * Committed records, newest observation first. Supports the field
     * dashboard's council and status filters.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'urban_council' => ['sometimes', 'string', 'max:255'],
            'pdp_status' => ['sometimes', 'string'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:200'],
        ]);

        $submissions = PdpSubmission::query()
            ->when(isset($validated['urban_council']), fn ($q) => $q->forCouncil($validated['urban_council']))
            ->when(isset($validated['pdp_status']), fn ($q) => $q->where('pdp_status', $validated['pdp_status']))
            ->orderByDesc('field_officer_timestamp')
            ->paginate($validated['per_page'] ?? 50)
            ->withQueryString();

        return response()->json([
            'data' => PdpSubmissionResource::collection($submissions->items()),
            'meta' => [
                'current_page' => $submissions->currentPage(),
                'per_page' => $submissions->perPage(),
                'total' => $submissions->total(),
            ],
        ]);
    }

    /**
     * Flush history, for the operations dashboard and incident forensics.
     */
    public function runs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:200'],
        ]);

        $runs = SyncRun::query()
            ->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 25)
            ->withQueryString();

        return response()->json([
            'data' => SyncRunResource::collection($runs->items()),
            'meta' => [
                'current_page' => $runs->currentPage(),
                'per_page' => $runs->perPage(),
                'total' => $runs->total(),
            ],
        ]);
    }
}
