<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smart_scheduler_runs', function (Blueprint $table) {
            // Use a string primary key so we can store ULID (26) or UUID (36)
            $table->string('id', 36)->primary();
            $table->string('command')->nullable();
            $table->string('status')->index(); // running|success|failed|skipped
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->text('error_message')->nullable();
            
            $table->string('hash', 36)->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smart_scheduler_runs');
    }
};
