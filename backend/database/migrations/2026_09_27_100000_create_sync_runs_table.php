<?php

declare(strict_types=1);

use App\Enums\SyncOutcome;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_runs', function (Blueprint $table): void {
            $table->id();

            // Idempotency key minted by the field client for the flush itself,
            // so a retried flush over HTTP is recognised server-side.
            $table->uuid('client_batch_id')->unique();

            $table->uuid('device_id')->nullable();
            $table->enum('outcome', SyncOutcome::values());

            $table->unsignedInteger('received_count')->default(0);
            $table->unsignedInteger('committed_count')->default(0);
            $table->unsignedInteger('duplicate_count')->default(0);
            $table->unsignedInteger('rejected_count')->default(0);

            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('finished_at')->nullable();

            $table->timestampsTz();

            $table->index('outcome');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_runs');
    }
};
