<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('metadata_logging', function (Blueprint $table) {
            $table->id();
            $table->string('metadata_name');      // task name or metadata
            $table->string('endpoint')->nullable();
            $table->text('response_message')->nullable();
            $table->string('response_code')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('metadata_name');
            $table->index('endpoint');
            $table->index('response_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metadata_logging');
    }
};
