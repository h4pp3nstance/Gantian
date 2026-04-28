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
        Schema::create('booking_inspections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('inspected_by')->constrained('users')->restrictOnDelete();
            $table->enum('condition_status', ['good', 'damaged', 'missing_accessory', 'late_return'])
                ->index();
            $table->text('notes')->nullable();
            $table->timestamp('inspected_at');
            $table->timestamps();

            $table->index(['condition_status', 'inspected_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_inspections');
    }
};
