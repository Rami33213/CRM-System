<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            
            $table->string('item_type');
            $table->text('description');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            
            $table->text('specifications')->nullable();
            $table->integer('estimated_hours')->nullable();
            $table->text('deliverables')->nullable();
            
            $table->enum('status', [
                'pending',
                'in_progress',
                'under_review',
                'completed',
                'cancelled'
            ])->default('pending');
            
            $table->integer('progress_percentage')->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            
            $table->timestamps();
            
            $table->index('order_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};