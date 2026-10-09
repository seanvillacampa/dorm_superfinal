<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "   BILLING STATEMENT VERIFICATION REPORT\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

// 1. Check total billing statements created
$totalStatements = \App\Models\Invoice::count();
echo "📊 TOTAL BILLING STATEMENTS: {$totalStatements}\n\n";

// 2. Verify BILL prefix
$billPrefixCount = \App\Models\Invoice::where('invoice_number', 'like', 'BILL-%')->count();
$invPrefixCount = \App\Models\Invoice::where('invoice_number', 'like', 'INV-%')->count();

echo "✅ Statements with BILL- prefix: {$billPrefixCount}\n";
if ($invPrefixCount > 0) {
    echo "⚠️  Statements with old INV- prefix: {$invPrefixCount}\n";
}
echo "\n";

// 3. Show billing statements by due date
echo "📅 BILLING STATEMENTS BY DUE DATE:\n";
echo str_repeat('-', 63) . "\n";

$statementsByDueDay = \App\Models\Invoice::selectRaw('DAY(due_date) as due_day, COUNT(*) as count')
    ->groupBy('due_day')
    ->orderBy('due_day')
    ->get();

foreach ($statementsByDueDay as $row) {
    echo sprintf("   Due on day %2d: %2d billing statement(s)\n", $row->due_day, $row->count);
}
echo "\n";

// 4. Show recent billing statements
echo "📋 RECENT BILLING STATEMENTS (Latest 10):\n";
echo str_repeat('-', 63) . "\n";
printf("%-18s %-15s %-12s %-10s\n", "Statement Number", "Room", "Billing Month", "Due Date");
echo str_repeat('-', 63) . "\n";

$recentStatements = \App\Models\Invoice::with('contract.room')
    ->orderBy('created_at', 'desc')
    ->take(10)
    ->get();

foreach ($recentStatements as $statement) {
    printf(
        "%-18s %-15s %-12s %-10s\n",
        $statement->invoice_number,
        'Room ' . $statement->contract->room->room_number,
        $statement->billing_month,
        $statement->due_date->format('Y-m-d')
    );
}
echo "\n";

// 5. Verify automatic generation
echo "🤖 AUTOMATIC GENERATION TEST:\n";
echo str_repeat('-', 63) . "\n";

$advanceDays = \App\Models\Setting::where('key', 'invoice_advance_days')->value('value') ?? 5;
$today = now();
$targetMonth = $today->copy()->format('Y-m');

echo "Today's date: " . $today->format('Y-m-d') . "\n";
echo "Advance days setting: {$advanceDays} days\n";
echo "Target billing month: {$targetMonth}\n\n";

// Check which contracts should have billing statements generated
$contracts = \App\Models\Contract::where('is_active', true)
    ->with('room')
    ->get();

$shouldGenerate = [];
$alreadyGenerated = [];

foreach ($contracts as $contract) {
    $dueDate = \Carbon\Carbon::createFromFormat('Y-m', $targetMonth)->day($contract->due_day);
    $generateDate = $dueDate->copy()->subDays($advanceDays);
    
    $existingStatement = \App\Models\Invoice::where('contract_id', $contract->id)
        ->where('billing_month', $targetMonth)
        ->first();
    
    if ($today->gte($generateDate)) {
        if ($existingStatement) {
            $alreadyGenerated[] = [
                'room' => $contract->room->room_number,
                'due_day' => $contract->due_day,
                'statement' => $existingStatement->invoice_number,
                'due_date' => $dueDate->format('Y-m-d'),
            ];
        } else {
            $shouldGenerate[] = [
                'room' => $contract->room->room_number,
                'due_day' => $contract->due_day,
                'due_date' => $dueDate->format('Y-m-d'),
            ];
        }
    }
}

echo "✅ Already Generated ({$today->format('Y-m-d')}): " . count($alreadyGenerated) . " billing statement(s)\n";
foreach ($alreadyGenerated as $item) {
    echo sprintf(
        "   Room %s (due: %s) → %s\n",
        $item['room'],
        $item['due_date'],
        $item['statement']
    );
}

if (count($shouldGenerate) > 0) {
    echo "\n⚠️  Pending Generation: " . count($shouldGenerate) . " billing statement(s)\n";
    foreach ($shouldGenerate as $item) {
        echo sprintf(
            "   Room %s (due: %s) - Should be generated today\n",
            $item['room'],
            $item['due_date']
        );
    }
} else {
    echo "\n✅ All eligible billing statements have been generated!\n";
}

// 6. Verify tenant payments were created
echo "\n";
echo "👥 TENANT PAYMENT RECORDS:\n";
echo str_repeat('-', 63) . "\n";

$tenantPaymentCount = \App\Models\TenantPayment::count();
echo "Total tenant payment records: {$tenantPaymentCount}\n";

$statementWithTenants = \App\Models\Invoice::withCount('tenantPayments')
    ->orderBy('created_at', 'desc')
    ->take(5)
    ->get();

echo "\nRecent billing statements with tenant payments:\n";
foreach ($statementWithTenants as $statement) {
    echo sprintf(
        "   %s: %d tenant payment(s)\n",
        $statement->invoice_number,
        $statement->tenant_payments_count
    );
}

// 7. Verify meter readings
echo "\n";
echo "⚡ METER READINGS:\n";
echo str_repeat('-', 63) . "\n";

$meterReadingCount = \App\Models\MeterReading::count();
echo "Total meter readings: {$meterReadingCount}\n";

$recentReadings = \App\Models\MeterReading::with('room')
    ->orderBy('reading_month', 'desc')
    ->take(5)
    ->get();

echo "\nRecent meter readings:\n";
foreach ($recentReadings as $reading) {
    echo sprintf(
        "   Room %s (%s): %d kWh used\n",
        $reading->room->room_number,
        $reading->reading_month,
        $reading->kwh_used
    );
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "   ✅ VERIFICATION COMPLETE\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";
