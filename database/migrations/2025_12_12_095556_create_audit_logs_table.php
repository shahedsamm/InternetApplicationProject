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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
              $table->foreignId('complaint_id')->nullable()
              ->constrained('complaints')
              ->cascadeOnDelete();

        $table->foreignId('user_id')->nullable()
              ->constrained('users')
              ->nullOnDelete();

        $table->enum('user_type', ['citizen', 'employee']);

        $table->string('action'); 
        // created, viewed, updated, deleted, status_changed, attachment_added, attachment_removed

        $table->json('changes')->nullable(); 
        // قبل وبعد التعديل

        $table->ipAddress('ip')->nullable();
        $table->string('user_agent')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
