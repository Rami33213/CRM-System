<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\Customer;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        // إنشاء موظفين
        $employees = [
            [
                'name' => 'محمد أحمد',
                'email' => 'mohammed.ahmed@company.com',
                'phone' => '+970591111111',
                'position' => 'Sales Manager',
                'department' => 'Sales',
                'status' => 'active',
                'hire_date' => '2023-01-15',
                'salary' => 3000.00,
                'address' => 'رام الله، فلسطين'
            ],
            [
                'name' => 'سارة خالد',
                'email' => 'sara.khaled@company.com',
                'phone' => '+970592222222',
                'position' => 'Account Manager',
                'department' => 'Sales',
                'status' => 'active',
                'hire_date' => '2023-03-20',
                'salary' => 2500.00,
                'address' => 'نابلس، فلسطين'
            ],
            [
                'name' => 'أحمد عبدالله',
                'email' => 'ahmed.abdullah@company.com',
                'phone' => '+970593333333',
                'position' => 'Customer Success Manager',
                'department' => 'Customer Success',
                'status' => 'active',
                'hire_date' => '2023-05-10',
                'salary' => 2800.00,
                'address' => 'الخليل، فلسطين'
            ],
            [
                'name' => 'ليلى حسن',
                'email' => 'laila.hassan@company.com',
                'phone' => '+970594444444',
                'position' => 'Sales Representative',
                'department' => 'Sales',
                'status' => 'active',
                'hire_date' => '2023-07-01',
                'salary' => 2200.00,
                'address' => 'غزة، فلسطين'
            ],
            [
                'name' => 'عمر يوسف',
                'email' => 'omar.yousef@company.com',
                'phone' => '+970595555555',
                'position' => 'Senior Account Manager',
                'department' => 'Sales',
                'status' => 'active',
                'hire_date' => '2022-11-15',
                'salary' => 3200.00,
                'address' => 'بيت لحم، فلسطين'
            ],
            [
                'name' => 'فاطمة إبراهيم',
                'email' => 'fatima.ibrahim@company.com',
                'phone' => '+970596666666',
                'position' => 'Customer Support Specialist',
                'department' => 'Customer Success',
                'status' => 'active',
                'hire_date' => '2024-01-10',
                'salary' => 2000.00,
                'address' => 'جنين، فلسطين'
            ],
            [
                'name' => 'خالد محمود',
                'email' => 'khaled.mahmoud@company.com',
                'phone' => '+970597777777',
                'position' => 'Team Leader',
                'department' => 'Sales',
                'status' => 'on_leave',
                'hire_date' => '2022-08-01',
                'salary' => 3500.00,
                'address' => 'طولكرم، فلسطين'
            ],
            [
                'name' => 'نور الدين',
                'email' => 'nour.aldeen@company.com',
                'phone' => '+970598888888',
                'position' => 'Junior Sales Representative',
                'department' => 'Sales',
                'status' => 'active',
                'hire_date' => '2024-06-01',
                'salary' => 1800.00,
                'address' => 'قلقيلية، فلسطين'
            ]
        ];

        foreach ($employees as $employeeData) {
            Employee::create($employeeData);
        }

        echo "✅ تم إنشاء " . count($employees) . " موظفين\n";

        // ربط العملاء بالموظفين بشكل عشوائي
        $this->assignCustomersToEmployees();
    }

    private function assignCustomersToEmployees()
    {
        $employees = Employee::where('status', 'active')->get();
        $customers = Customer::all();

        if ($employees->isEmpty() || $customers->isEmpty()) {
            echo "⚠️  لا يوجد موظفين أو عملاء للربط\n";
            return;
        }

        $assignedCount = 0;
        
        foreach ($customers as $customer) {
            // ربط عشوائي (70% من العملاء سيتم ربطهم)
            if (rand(1, 100) <= 70) {
                $randomEmployee = $employees->random();
                $customer->employee_id = $randomEmployee->id;
                $customer->save();
                $assignedCount++;
            }
        }

        echo "✅ تم ربط {$assignedCount} عميل مع الموظفين\n";
        echo "ℹ️  العملاء بدون موظف: " . Customer::whereNull('employee_id')->count() . "\n";
    }
}