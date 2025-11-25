<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('employee_id')
                ->nullable()
                ->after('customer_segment_id')
                ->constrained('employees')
                ->onDelete('set null'); // إذا تم حذف الموظف، العملاء يصيروا بدون موظف
            
            $table->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropIndex(['employee_id']);
            $table->dropColumn('employee_id');
        });
    }
};