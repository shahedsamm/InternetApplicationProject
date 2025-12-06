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
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('citizen_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['noise', 'garbage', 'infrastructure', 'other']);
            $table->enum('section', ['security', 'finance', 'education']);
            $table->text('location');
            $table->string('national_id')->index();
            $table->text('description');
            $table->json('attachments')->nullable(); 
            $table->string('serial_number')->unique()->nullable();
            $table->enum('status', ['new','pending','done','rejected'])->default('new');
            $table->text('notes')->nullable();
            $table->boolean('locked')->default(false);
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
