<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['score']); // حذف الـ index
            $table->dropColumn('score'); // حذف العمود
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('score', 5, 2)->nullable()->after('address');
            $table->index('score');
        });
    }
};