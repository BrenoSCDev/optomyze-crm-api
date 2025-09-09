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
        Schema::create('stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funnel_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('order')->default(1);
            $table->enum('type', [
                'entry',
                'normal',
                'service',
                'proposition',
                'qualified',
                'conversion',
                'lost'
            ]);
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable(); // Stage-specific settings like SLA, auto-actions, etc.
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['funnel_id', 'order']);
            $table->index(['funnel_id', 'is_active', 'deleted_at']);
            $table->index('order');
            
            // Unique constraint to prevent duplicate orders within a funnel
            $table->unique(['funnel_id', 'order', 'deleted_at'], 'stages_funnel_order_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stages');
    }
};