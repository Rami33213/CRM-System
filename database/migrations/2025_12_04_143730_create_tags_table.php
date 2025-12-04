<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // اسم التاغ (مثل: "ويب سايت أخضر")
            $table->string('slug')->unique(); // للبحث السريع
            $table->string('color')->default('#3B82F6'); // لون التاغ (hex)
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index('name');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};