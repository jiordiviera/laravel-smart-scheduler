<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('smart_schedule_runs', function (Blueprint $table) {
            $table->id();
            $table->string('task_identifier', 255);
            $table->string('status', 50);
            $table->dateTime('started_at');
            $table->dateTime('finished_at')->nullable();
            $table->decimal('duration', 8, 3)->nullable();
            $table->text('output')->nullable();
            $table->text('exception')->nullable();
            $table->string('server_name', 100);
            $table->timestamps();

            // Index pour la performance
            $table->index(['task_identifier', 'status', 'started_at']);
            $table->index(['status', 'started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('smart_schedule_runs');
    }
};
