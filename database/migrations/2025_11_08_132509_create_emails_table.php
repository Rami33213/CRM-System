<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmailsTable extends Migration
{
    public function up()
    {
        Schema::create('emails', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();

            // If you have users table; nullable for system-sent messages
            $table->unsignedBigInteger('sender_id')->nullable()->index();
            $table->unsignedBigInteger('receiver_id')->nullable()->index();

            $table->string('subject', 255)->nullable();
            $table->longText('body');

            // attachments as JSON array of files metadata: [{name,url,size,type},...]
            $table->json('attachments')->nullable();

            // thread for replies (self reference)
            $table->unsignedBigInteger('thread_id')->nullable()->index();

            // flags and read status
            $table->boolean('is_flagged')->default(false)->index(); // 0 or 1
            $table->boolean('is_read')->default(false)->index(); // whether user read it
            $table->timestamp('read_at')->nullable()->index();

            // delivery timestamps
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('sent_at')->nullable()->index();

            // priority
            $table->enum('priority', ['low','normal','high'])->default('normal')->index();

            // soft deletes and timestamps
            $table->softDeletes();
            $table->timestamps();

            // foreign keys (optional; uncomment if you want FK constraints)
            // $table->foreign('sender_id')->references('id')->on('users')->onDelete('set null');
            // $table->foreign('receiver_id')->references('id')->on('users')->onDelete('set null');
            // $table->foreign('thread_id')->references('id')->on('emails')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('emails');
    }
};


