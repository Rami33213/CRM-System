<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Service;

class CustomerOrderSeeder extends Seeder
{
    public function run(): void
    {
        // إنشاء عملاء تجريبيين
        $customers = [
            [
                'customer_segment_id' => 1, // أضف segment_id أو null
                'name' => 'أحمد محمد',
                'email' => 'ahmed@example.com',
                'phone' => '+970591234567',
                'address' => 'رام الله، فلسطين'
            ],
            [
                'customer_segment_id' => 1,
                'name' => 'سارة أحمد',
                'email' => 'sara@example.com',
                'phone' => '+970592345678',
                'address' => 'نابلس، فلسطين'
            ],
            [
                'customer_segment_id' => 1,
                'name' => 'محمد علي',
                'email' => 'mohammed@example.com',
                'phone' => '+970593456789',
                'address' => 'الخليل، فلسطين'
            ],
            [
                'customer_segment_id' => 1,
                'name' => 'فاطمة حسن',
                'email' => 'fatima@example.com',
                'phone' => '+970594567890',
                'address' => 'غزة، فلسطين'
            ],
            [
                'customer_segment_id' => 1,
                'name' => 'عمر خالد',
                'email' => 'omar@example.com',
                'phone' => '+970595678901',
                'address' => 'بيت لحم، فلسطين'
            ]
        ];

        foreach ($customers as $customerData) {
            $customer = Customer::create($customerData);

            // إنشاء طلبات عشوائية لكل عميل
            $numOrders = rand(1, 3);

            for ($i = 0; $i < $numOrders; $i++) {
                $this->createRandomOrder($customer);
            }
        }

        // إنشاء طلب مثالي محدد (المثال من السؤال)
        $this->createExampleOrder();
    }

    private function createRandomOrder($customer)
    {
        $statuses = ['pending', 'confirmed', 'in_progress', 'completed'];
        $sources = ['whatsapp', 'email', 'phone', 'website'];
        $paymentStatuses = ['unpaid', 'partially_paid', 'paid'];

        $order = Order::create([
            'customer_id' => $customer->id,
            'status' => $statuses[array_rand($statuses)],
            'discount' => rand(0, 500),
            'tax' => rand(0, 200),
            'notes' => 'ملاحظات العميل حول الطلب',
            'internal_notes' => 'ملاحظات داخلية للفريق',
            'source' => $sources[array_rand($sources)],
            'expected_delivery_date' => now()->addDays(rand(15, 60)),
            'payment_status' => $paymentStatuses[array_rand($paymentStatuses)]
        ]);

        // إضافة عناصر عشوائية للطلب
        $services = Service::active()->inRandomOrder()->limit(rand(1, 4))->get();

        foreach ($services as $service) {
            OrderItem::create([
                'order_id' => $order->id,
                'service_id' => $service->id,
                'quantity' => rand(1, 3),
                'unit_price' => $service->base_price,
                'discount' => rand(0, 100),
                'customization_notes' => 'متطلبات خاصة للخدمة',
                'specifications' => [
                    'color_scheme' => 'blue',
                    'platform' => 'mobile',
                    'features' => ['auth', 'dashboard', 'reports']
                ]
            ]);
        }

        // تحديث مبلغ مدفوع عشوائي
        // احسب المجموع أولاً
        $order->calculateTotals();

        // ثم حدّث مبلغ الدفع بناءً على المجموع
        if ($order->payment_status !== 'unpaid') {
            $order->paid_amount = $order->payment_status === 'paid'
                ? $order->total
                : $order->total * 0.5;
            $order->save();
        }

        $order->calculateTotals();
    }

    private function createExampleOrder()
    {
        // العميل من المثال
        $customer = Customer::create([
            'customer_segment_id' => 1, // أو أي ID موجود فعليًا في جدول customer_segments
            'name' => 'عميل من الواتساب',
            'email' => 'whatsapp.customer@example.com',
            'phone' => '+970599999999',
            'address' => 'رام الله، فلسطين'
        ]);

        // الطلب من المثال
        $order = Order::create([
            'customer_id' => $customer->id,
            'status' => 'pending',
            'source' => 'whatsapp',
            'notes' => 'طلب من الواتساب - يريد تطبيق فلاتر وتطبيقين ويب',
            'expected_delivery_date' => now()->addDays(30)
        ]);

        // الخدمات من المثال
        $flutterService = Service::where('name', 'LIKE', '%فلاتر%')->first();
        $webService = Service::where('name', 'LIKE', '%ويب%')->first();

        // تطبيق فلاتر - عدد 1 - سعر 200
        if ($flutterService) {
            OrderItem::create([
                'order_id' => $order->id,
                'service_id' => $flutterService->id,
                'quantity' => 1,
                'unit_price' => 200,
                'discount' => 0,
                'customization_notes' => 'تطبيق فلاتر للأندرويد و iOS'
            ]);
        }

        // تطبيق ويب - عدد 2 - سعر 300 للواحد
        if ($webService) {
            OrderItem::create([
                'order_id' => $order->id,
                'service_id' => $webService->id,
                'quantity' => 2,
                'unit_price' => 300,
                'discount' => 0,
                'customization_notes' => 'تطبيقان ويب بتقنيات حديثة'
            ]);
        }

        $order->calculateTotals();

        echo "✅ تم إنشاء الطلب المثالي: {$order->order_number}\n";
        echo "   المجموع: {$order->total} USD\n";
    }
}