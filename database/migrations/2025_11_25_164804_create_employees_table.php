<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->string('position')->nullable(); // المسمى الوظيفي
            $table->string('department')->nullable(); // القسم
            $table->enum('status', ['active', 'inactive', 'on_leave'])->default('active');
            $table->date('hire_date')->nullable(); // تاريخ التوظيف
            $table->decimal('salary', 10, 2)->nullable();
            $table->text('address')->nullable();
            $table->string('avatar')->nullable(); // صورة الموظف
            $table->json('permissions')->nullable(); // صلاحيات إضافية
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('email');
            $table->index('phone');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};