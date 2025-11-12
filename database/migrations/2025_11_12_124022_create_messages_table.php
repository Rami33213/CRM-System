<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->enum('sender_type', ['customer', 'system'])->default('customer');
            $table->enum('message_type', ['incoming', 'outgoing']);
            $table->string('subject')->nullable();
            $table->longText('body');
            $table->enum('status', ['unread', 'read', 'archived'])->default('unread');
            $table->string('sender_name')->nullable();
            $table->string('receiver_name')->nullable();
            $table->timestamps();
            
            $table->index('customer_id');
            $table->index('message_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};