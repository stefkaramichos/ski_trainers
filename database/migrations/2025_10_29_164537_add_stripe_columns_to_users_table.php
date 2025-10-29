<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('stripe_customer_id', 255)
                  ->nullable()
                  ->after('status');

            $table->string('stripe_subscription_id', 255)
                  ->nullable()
                  ->after('stripe_customer_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('stripe_customer_id');
            $table->dropColumn('stripe_subscription_id');
        });
    }
};
