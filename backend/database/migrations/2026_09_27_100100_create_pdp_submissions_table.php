<?php

declare(strict_types=1);

use App\Enums\PdpStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdp_submissions', function (Blueprint $table): void {
            // Client-generated idempotency key. Primary key *and* the single
            // source of truth for de-duplication, so no separate unique index
            // is required: the PK constraint is what rejects a replay.
            $table->uuid('submission_uuid')->primary();

            $table->string('urban_council', 255);
            $table->enum('pdp_status', PdpStatus::values());

            // Nullable by design: a "Missing" plan has no expiry year.
            $table->unsignedSmallInteger('expiry_year')->nullable();

            // When the officer captured the record, not when we received it.
            $table->timestampTz('field_officer_timestamp');

            // Ingestion bookkeeping for auditability and incident forensics.
            $table->foreignId('sync_run_id')->nullable()->constrained('sync_runs')->nullOnDelete();
            $table->uuid('device_id')->nullable();
            $table->unsignedInteger('payload_hash')->nullable();
            $table->timestampTz('committed_at')->nullable();
            $table->unsignedInteger('ingest_sequence')->nullable();

            $table->timestampsTz();

            $table->index('urban_council');
            $table->index('pdp_status');
            $table->index('field_officer_timestamp');
            $table->index('ingest_sequence');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdp_submissions');
    }
};
