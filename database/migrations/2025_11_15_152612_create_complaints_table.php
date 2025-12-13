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
             $table->string('type')->nullable();
            $table->enum('section', ['وزارةالكهربا','وزارةالمياه','وزارةالاتصالات','وزارةالصحة','وزارةالتربية']);
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
             $table->charset = 'utf8mb4';
             $table->collation = 'utf8mb4_unicode_ci';
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
