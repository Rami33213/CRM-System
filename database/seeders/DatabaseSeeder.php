<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CustomerOrderSeeder::class,
        ]);

        echo "\n✅ تم تعبئة قاعدة البيانات بنجاح!\n";
        echo "📊 البيانات المضافة:\n";
        echo "   - 3 تصنيفات عملاء\n";
        echo "   - 10 خدمات\n";
        echo "   - 6 عملاء\n";
        echo "   - عدة طلبات مع عناصرها\n";
        echo "   - طلب مثالي من الواتساب\n\n";
    }
}