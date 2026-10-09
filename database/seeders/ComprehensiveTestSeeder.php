<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\Contract;
use App\Models\MeterReading;
use App\Models\Setting;

class ComprehensiveTestSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Clear existing data
        DB::table('tenant_payments')->truncate();
        DB::table('payments')->truncate();
        DB::table('invoices')->truncate();
        DB::table('meter_readings')->truncate();
        DB::table('contract_tenants')->truncate();
        DB::table('contracts')->truncate();
        DB::table('tenants')->truncate();
        DB::table('rooms')->truncate();
        User::whereHas('roles', function($q) {
            $q->where('name', 'tenant');
        })->delete();
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('🧹 Cleared existing data');

        // Create settings
        Setting::updateOrCreate(['key' => 'electricity_rate'], ['value' => '12.50']);
        Setting::updateOrCreate(['key' => 'invoice_advance_days'], ['value' => '5']);
        Setting::updateOrCreate(['key' => 'grace_period_days'], ['value' => '3']);

        $this->command->info('⚙️  Settings configured');

        // Get owner user
        $owner = User::role('owner')->first();
        if (!$owner) {
            $this->command->error('❌ No owner user found! Run DatabaseSeeder first.');
            return;
        }

        // Room configurations with varied rates
        $roomConfigs = [
            // Floor 1 - Budget rooms
            ['floor' => 1, 'numbers' => ['101', '102', '103', '104', '105'], 'rate' => 3500],
            // Floor 2 - Standard rooms
            ['floor' => 2, 'numbers' => ['201', '202', '203', '204', '205'], 'rate' => 4000],
            // Floor 3 - Premium rooms
            ['floor' => 3, 'numbers' => ['301', '302', '303', '304', '305'], 'rate' => 4500],
            // Floor 4 - Deluxe rooms
            ['floor' => 4, 'numbers' => ['401', '402', '403', '404', '405'], 'rate' => 5000],
        ];

        $rooms = [];
        foreach ($roomConfigs as $config) {
            foreach ($config['numbers'] as $number) {
                $rooms[] = Room::create([
                    'room_number' => $number,
                    'floor' => $config['floor'],
                    'monthly_rate' => $config['rate'],
                    'capacity' => rand(2, 4),
                    'deposit_required' => $config['rate'],
                    'meter_number' => 'MTR-' . $number,
                    'status' => 'occupied',
                    'is_airconditioned' => (bool)rand(0, 1),
                ]);
            }
        }

        $this->command->info('🏠 Created ' . count($rooms) . ' rooms with varied rates');

        // Filipino first names and last names
        $firstNames = ['Juan', 'Maria', 'Jose', 'Ana', 'Pedro', 'Sofia', 'Miguel', 'Isabella', 'Carlos', 'Elena', 
                       'Luis', 'Carmen', 'Diego', 'Rosa', 'Fernando', 'Lucia', 'Antonio', 'Teresa', 'Rafael', 'Josefina',
                       'Manuel', 'Patricia', 'Gabriel', 'Monica', 'Ricardo', 'Angela', 'Roberto', 'Diana', 'Javier', 'Laura'];
        
        $lastNames = ['Santos', 'Reyes', 'Cruz', 'Bautista', 'Garcia', 'Mendoza', 'Lopez', 'Gonzales', 'Rodriguez', 'Fernandez',
                      'Martinez', 'Torres', 'Rivera', 'Ramos', 'Diaz', 'Morales', 'Castillo', 'Aguilar', 'Flores', 'Valdez'];

        // Possible due dates (varied for testing automatic generation)
        $dueDays = [5, 10, 15, 20, 25];

        $contractsCreated = 0;
        $tenantsCreated = 0;

        // Create contracts with varied configurations
        foreach ($rooms as $index => $room) {
            // Randomize number of tenants (1-3)
            $tenantCount = rand(1, 3);
            
            // Random due day
            $dueDay = $dueDays[array_rand($dueDays)];

            // Create contract
            $startDate = now()->subMonths(rand(1, 6))->startOfMonth();
            $contract = Contract::create([
                'room_id' => $room->id,
                'start_date' => $startDate,
                'end_date' => null,
                'due_day' => $dueDay,
                'deposit_collected' => $room->deposit_required * $tenantCount,
                'status' => 'active',
                'is_active' => true,
                'created_by' => $owner->id,
            ]);

            $contractsCreated++;

            // Create tenants for this contract
            for ($i = 0; $i < $tenantCount; $i++) {
                $firstName = $firstNames[array_rand($firstNames)];
                $middleName = $firstNames[array_rand($firstNames)];
                $lastName = $lastNames[array_rand($lastNames)];
                $fullName = "{$firstName} {$middleName} {$lastName}";
                
                // Create user
                $user = User::create([
                    'first_name' => $firstName,
                    'middle_name' => $middleName,
                    'last_name' => $lastName,
                    'name' => $fullName,
                    'email' => strtolower(str_replace(' ', '.', $firstName . $lastName . rand(1, 999))) . '@example.com',
                    'phone' => '09' . rand(100000000, 999999999),
                    'phone_country_code' => '+63',
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ]);
                $user->assignRole('tenant');

                // Create tenant
                $tenant = Tenant::create([
                    'user_id' => $user->id,
                    'birthdate' => now()->subYears(rand(20, 35))->format('Y-m-d'),
                    'emergency_name' => $fullName . ' (Parent)',
                    'emergency_phone' => '09' . rand(100000000, 999999999),
                    'id_type' => ['national_id', 'drivers_license', 'passport'][rand(0, 2)],
                    'id_number' => strtoupper(substr(md5(rand()), 0, 12)),
                ]);

                // Attach tenant to contract
                $contract->tenants()->attach($tenant->id);
                $tenantsCreated++;
            }

            // Create meter readings for the past 3 months
            $previousReading = rand(1000, 5000);
            for ($monthsAgo = 3; $monthsAgo >= 1; $monthsAgo--) {
                $readingMonth = now()->subMonths($monthsAgo)->format('Y-m');
                $currentReading = $previousReading + rand(50, 200);
                $kwhUsed = $currentReading - $previousReading;

                MeterReading::create([
                    'room_id' => $room->id,
                    'reading_month' => $readingMonth,
                    'previous_kwh' => $previousReading,
                    'current_kwh' => $currentReading,
                    'kwh_used' => $kwhUsed,
                    'recorded_by' => $owner->id,
                ]);

                $previousReading = $currentReading;
            }
        }

        $this->command->info("👥 Created {$contractsCreated} contracts with {$tenantsCreated} total tenants");
        $this->command->info('📊 Created meter readings for the past 3 months');
        $this->command->newLine();
        $this->command->info('✅ Comprehensive test data created successfully!');
        $this->command->newLine();
        
        // Display summary
        $this->command->info('📋 SUMMARY:');
        $this->command->info('   Rooms: ' . count($rooms));
        $this->command->info('   Contracts: ' . $contractsCreated);
        $this->command->info('   Tenants: ' . $tenantsCreated);
        $this->command->info('   Due dates used: ' . implode(', ', array_unique($dueDays)));
        $this->command->newLine();
        
        // Show contract distribution by due date
        $this->command->info('📅 CONTRACTS BY DUE DATE:');
        foreach ($dueDays as $day) {
            $count = Contract::where('due_day', $day)->count();
            if ($count > 0) {
                $this->command->info("   Due on day {$day}: {$count} contract(s)");
            }
        }
        $this->command->newLine();
        $this->command->info('🔄 Next step: Run "php artisan invoices:generate-scheduled" to test automatic billing statement generation');
    }
}
