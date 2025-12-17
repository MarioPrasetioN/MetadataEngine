<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('playout_now_play', function (Blueprint $table) {
            $table->id();
            $table->string('playout_id');
            $table->string('artist')->nullable();
            $table->string('title')->nullable();
            $table->string('category')->nullable();
            $table->string('filename')->nullable();
            $table->integer('duration')->nullable();
            $table->string('start_time')->nullable();
            $table->string('planned_start_time')->nullable();
            $table->integer('cutout')->nullable();
            $table->integer('cutout_origin')->nullable();
            $table->integer('mix_point_pr_ev')->nullable();
            $table->integer('mix_point_pr_ev_origin')->nullable();
            $table->boolean('inserted_element')->default(false);
            $table->integer('drift_ms')->nullable();
            $table->string('playlist_date')->nullable();
            $table->integer('retryCount')->default(0);
            // Extra fields
            $table->string('local_code')->nullable();
            $table->string('playout_type')->nullable();
            $table->string('network_code')->nullable();
            // Created_at default to now
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Indexes
            $table->index('playout_id');
            $table->index('playlist_date');
            $table->index('network_code');
            $table->index(['network_code','playlist_date']);
            $table->index('local_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playout_now_play');
    }
};
