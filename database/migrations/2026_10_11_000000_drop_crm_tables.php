<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('order_staff');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('staff');
        Schema::dropIfExists('services');
        Schema::dropIfExists('customers');

        DB::table('migrations')->whereIn('migration', [
            '2026_07_05_100000_create_customers_table',
            '2026_07_05_100100_create_services_table',
            '2026_07_05_100200_create_staff_table',
            '2026_07_05_100300_create_orders_table',
            '2026_07_05_100400_create_order_staff_table',
        ])->delete();
    }

    public function down(): void
    {
    }
};
