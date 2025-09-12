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
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('provider_type');
            $table->text('description')->nullable();
            $table->string('csv_url');
            $table->string('version')->default('1.0');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_current_version')->default(false);
            $table->json('import_settings')->nullable();
            $table->json('mapping_config')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->enum('last_run_status', ['pending', 'running', 'completed', 'failed'])->nullable();
            $table->text('last_run_error')->nullable();
            $table->integer('records_processed')->default(0);
            $table->integer('records_created')->default(0);
            $table->integer('records_updated')->default(0);
            $table->integer('records_skipped')->default(0);
            $table->integer('records_missing')->default(0);
            $table->boolean('schedule_enabled')->default(false);
            $table->string('schedule_frequency')->nullable(); // daily, weekly, monthly
            $table->time('schedule_time')->nullable();
            $table->json('schedule_days')->nullable(); // for weekly scheduling
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imports');
    }
};
