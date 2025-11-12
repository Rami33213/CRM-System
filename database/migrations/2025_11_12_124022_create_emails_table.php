<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->enum('email_type', ['incoming', 'outgoing']);
            $table->string('from_email');
            $table->string('to_email');
            $table->string('cc_email')->nullable();
            $table->string('bcc_email')->nullable();
            $table->string('subject');
            $table->longText('body');
            $table->json('attachments')->nullable();
            $table->enum('status', ['draft', 'sent', 'received', 'read', 'replied'])->default('draft');
            $table->enum('priority', ['low', 'normal', 'high'])->default('normal');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            $table->index('customer_id');
            $table->index('email_type');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emails');
    }
};