<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_customer_progress_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::create('customer_progress', function (Blueprint $table) {
        $table->id();
        $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
        $table->enum('customer_status', ['prospect', 'negotiation', 'proposal_sent', 'deal_closed', 'on_hold']);
        $table->string('action');
        $table->longText('description')->nullable();
        $table->date('action_date');
        $table->timestamp('completed_at')->nullable();
        $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
        $table->unsignedBigInteger('created_by')->nullable();
        $table->timestamps();
        $table->index('customer_id');
        $table->index('customer_status');
        $table->index('action_date');
    });
}

    public function down(): void
    {
        Schema::dropIfExists('customer_progress');
    }
};