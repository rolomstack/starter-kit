<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('payment_method_id')->nullable()->after('plan_id')->constrained('payment_methods')->nullOnDelete();

            // In sqlite, we must drop the index before dropping the column.
            $table->dropIndex(['reference']);

            // We can drop the gateway-specific columns, or just make them nullable if they aren't already.
            // Since we're moving to App-driven billing, we'll drop them to enforce the new architecture.
            $table->dropColumn(['gateway_name', 'gateway_id', 'gateway_customer_id', 'reference']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['payment_method_id']);
            $table->dropColumn('payment_method_id');

            $table->string('gateway_name')->nullable();
            $table->string('gateway_id')->nullable();
            $table->string('gateway_customer_id')->nullable();
            $table->string('reference')->nullable();
        });
    }
};
