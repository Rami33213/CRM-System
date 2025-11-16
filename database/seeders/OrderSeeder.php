<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\Customer;
use App\Models\User;

class OrderSeeder extends Seeder
{
    
    public function run(): void
    {
        // تأكد من وجود عملاء ومستخدمين
        $customers = Customer::all();
        $users = User::all();

        if ($customers->isEmpty()) {
            echo "⚠️  Please create customers first!\n";
            return;
        }

        if ($users->isEmpty()) {
            echo "⚠️  Please create users first!\n";
            return;
        }

        echo "🚀 Creating sample orders...\n\n";

        // Order 1: طلب من واتساب - قيد الانتظار
        $order1 = Order::create([
            'customer_id' => $customers->first()->id,
            'source' => 'whatsapp',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'tax_rate' => 15,
            'discount_amount' => 0,
            'order_date' => now()->subDays(2),
            'expected_delivery_date' => now()->addDays(30),
            'notes' => 'العميل تواصل عبر الواتساب وطلب عرض سعر',
            'customer_requirements' => 'يريد تطبيق موبايل بسيط لإدارة المهام',
            'internal_notes' => 'عميل جديد - متابعة يومية',
            'created_by' => $users->first()->id,
            'total' => 0
        ]);

        $order1->items()->createMany([
            [
                'item_type' => 'Flutter Application',
                'description' => 'تطبيق إدارة مهام بنظام فلاتر',
                'quantity' => 1,
                'unit_price' => 200.00,
                'specifications' => 'Android & iOS - UI بسيط - قاعدة بيانات محلية',
                'estimated_hours' => 80,
                'deliverables' => 'تطبيق Android + iOS + كود مصدري',
                'status' => 'pending',
                'progress_percentage' => 0
            ]
        ]);

        echo "✅ Order 1 created: {$order1->order_number} (Pending)\n";

        // Order 2: طلب من الموقع - قيد التنفيذ
        $order2 = Order::create([
            'customer_id' => $customers->skip(1)->first()?->id ?? $customers->first()->id,
            'source' => 'website',
            'status' => 'in_progress',
            'payment_status' => 'partially_paid',
            'tax_rate' => 15,
            'discount_amount' => 50,
            'order_date' => now()->subDays(15),
            'expected_delivery_date' => now()->addDays(15),
            'notes' => 'طلب من خلال الموقع - دفع 50% مقدم',
            'customer_requirements' => 'موقع إلكتروني متجاوب مع لوحة تحكم كاملة',
            'internal_notes' => 'عميل مميز - الأولوية عالية',
            'created_by' => $users->first()->id,
            'assigned_to' => $users->skip(1)->first()?->id ?? $users->first()->id,
            'total' => 0
        ]);

        $order2->items()->createMany([
            [
                'item_type' => 'Web Application',
                'description' => 'موقع ويب متجاوب مع لوحة تحكم',
                'quantity' => 1,
                'unit_price' => 500.00,
                'specifications' => 'Laravel + Vue.js - Responsive - Admin Panel - User Dashboard',
                'estimated_hours' => 150,
                'deliverables' => 'موقع كامل + لوحة تحكم + قاعدة بيانات',
                'status' => 'in_progress',
                'progress_percentage' => 35,
                'start_date' => now()->subDays(10)
            ],
            [
                'item_type' => 'SEO Optimization',
                'description' => 'تحسين محركات البحث',
                'quantity' => 1,
                'unit_price' => 200.00,
                'specifications' => 'On-page SEO - Technical SEO - Performance optimization',
                'estimated_hours' => 40,
                'deliverables' => 'تقرير SEO + تحسينات تقنية',
                'status' => 'pending',
                'progress_percentage' => 0
            ]
        ]);

        echo "✅ Order 2 created: {$order2->order_number} (In Progress - 35%)\n";

        // Order 3: مشروع كبير - مكتمل
        $order3 = Order::create([
            'customer_id' => $customers->skip(2)->first()?->id ?? $customers->first()->id,
            'source' => 'email',
            'status' => 'completed',
            'payment_status' => 'paid',
            'tax_rate' => 15,
            'discount_amount' => 200,
            'order_date' => now()->subDays(60),
            'expected_delivery_date' => now()->subDays(10),
            'actual_delivery_date' => now()->subDays(5),
            'notes' => 'مشروع كبير - اكتمل بنجاح',
            'customer_requirements' => 'نظام CRM متكامل مع تطبيق موبايل',
            'internal_notes' => 'عميل ممتاز - طلب توثيق كامل',
            'created_by' => $users->first()->id,
            'assigned_to' => $users->first()->id,
            'total' => 0
        ]);

        $order3->items()->createMany([
            [
                'item_type' => 'CRM System',
                'description' => 'نظام إدارة علاقات العملاء',
                'quantity' => 1,
                'unit_price' => 1500.00,
                'specifications' => 'Full CRM - Customer Management - Sales Pipeline - Reports',
                'estimated_hours' => 300,
                'deliverables' => 'نظام CRM كامل + توثيق + تدريب',
                'status' => 'completed',
                'progress_percentage' => 100,
                'start_date' => now()->subDays(55),
                'end_date' => now()->subDays(5)
            ],
            [
                'item_type' => 'Mobile App',
                'description' => 'تطبيق موبايل للنظام',
                'quantity' => 1,
                'unit_price' => 800.00,
                'specifications' => 'Android & iOS - Sync with CRM - Real-time notifications',
                'estimated_hours' => 200,
                'deliverables' => 'تطبيق Android + iOS + API Integration',
                'status' => 'completed',
                'progress_percentage' => 100,
                'start_date' => now()->subDays(50),
                'end_date' => now()->subDays(5)
            ],
            [
                'item_type' => 'Training',
                'description' => 'تدريب الموظفين على النظام',
                'quantity' => 2,
                'unit_price' => 150.00,
                'specifications' => 'On-site training - 2 sessions - 4 hours each',
                'estimated_hours' => 8,
                'deliverables' => 'جلستين تدريب + دليل مستخدم',
                'status' => 'completed',
                'progress_percentage' => 100,
                'start_date' => now()->subDays(7),
                'end_date' => now()->subDays(5)
            ]
        ]);

        echo "✅ Order 3 created: {$order3->order_number} (Completed)\n";

        // Order 4: طلب من فيسبوك - مؤكد
        $order4 = Order::create([
            'customer_id' => $customers->first()->id,
            'source' => 'facebook',
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
            'tax_rate' => 15,
            'discount_amount' => 30,
            'order_date' => now()->subDays(3),
            'expected_delivery_date' => now()->addDays(20),
            'notes' => 'طلب عبر صفحة الفيسبوك',
            'customer_requirements' => 'تطبيق ويب بسيط للمطعم',
            'internal_notes' => 'انتظار الدفعة المقدمة',
            'created_by' => $users->first()->id,
            'total' => 0
        ]);

        $order4->items()->createMany([
            [
                'item_type' => 'Restaurant Website',
                'description' => 'موقع مطعم مع نظام طلبات',
                'quantity' => 1,
                'unit_price' => 400.00,
                'specifications' => 'Menu display - Online ordering - Responsive design',
                'estimated_hours' => 100,
                'deliverables' => 'موقع كامل + نظام طلبات',
                'status' => 'pending',
                'progress_percentage' => 0
            ],
            [
                'item_type' => 'Logo Design',
                'description' => 'تصميم شعار احترافي',
                'quantity' => 1,
                'unit_price' => 100.00,
                'specifications' => '3 concepts - Unlimited revisions - Source files',
                'estimated_hours' => 20,
                'deliverables' => 'شعار نهائي + ملفات مصدرية',
                'status' => 'pending',
                'progress_percentage' => 0
            ]
        ]);

        echo "✅ Order 4 created: {$order4->order_number} (Confirmed)\n";

        // Order 5: طلب معلق
        $order5 = Order::create([
            'customer_id' => $customers->skip(1)->first()?->id ?? $customers->first()->id,
            'source' => 'phone',
            'status' => 'on_hold',
            'payment_status' => 'unpaid',
            'tax_rate' => 15,
            'discount_amount' => 0,
            'order_date' => now()->subDays(7),
            'expected_delivery_date' => now()->addDays(30),
            'notes' => 'الطلب معلق - انتظار موافقة العميل',
            'customer_requirements' => 'نظام محاسبي بسيط',
            'internal_notes' => 'العميل يفكر في الميزانية',
            'created_by' => $users->first()->id,
            'total' => 0
        ]);

        $order5->items()->createMany([
            [
                'item_type' => 'Accounting System',
                'description' => 'نظام محاسبة بسيط',
                'quantity' => 1,
                'unit_price' => 600.00,
                'specifications' => 'Invoice management - Expense tracking - Basic reports',
                'estimated_hours' => 120,
                'deliverables' => 'نظام محاسبة + تدريب',
                'status' => 'pending',
                'progress_percentage' => 0
            ]
        ]);

        echo "✅ Order 5 created: {$order5->order_number} (On Hold)\n";

        // Order 6: طلب ملغي
        $order6 = Order::create([
            'customer_id' => $customers->skip(2)->first()?->id ?? $customers->first()->id,
            'source' => 'direct',
            'status' => 'cancelled',
            'payment_status' => 'refunded',
            'tax_rate' => 15,
            'discount_amount' => 0,
            'order_date' => now()->subDays(20),
            'notes' => 'ألغى العميل الطلب',
            'customer_requirements' => 'تطبيق توصيل',
            'internal_notes' => 'تم استرجاع المبلغ كاملاً',
            'created_by' => $users->first()->id,
            'total' => 0
        ]);

        $order6->items()->createMany([
            [
                'item_type' => 'Delivery App',
                'description' => 'تطبيق توصيل طلبات',
                'quantity' => 1,
                'unit_price' => 1000.00,
                'specifications' => 'Customer app - Driver app - Admin panel',
                'estimated_hours' => 250,
                'deliverables' => '3 تطبيقات متكاملة',
                'status' => 'cancelled',
                'progress_percentage' => 0
            ]
        ]);

        echo "✅ Order 6 created: {$order6->order_number} (Cancelled)\n\n";

        echo "🎉 Successfully created 6 sample orders with items!\n";
        echo "📊 Summary:\n";
        echo "   - 1 Pending order\n";
        echo "   - 1 Confirmed order\n";
        echo "   - 1 In Progress order (35% complete)\n";
        echo "   - 1 On Hold order\n";
        echo "   - 1 Completed order (100% complete)\n";
        echo "   - 1 Cancelled order\n";
    }
}