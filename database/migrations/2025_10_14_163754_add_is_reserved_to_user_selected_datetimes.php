<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_selected_datetimes', function (Blueprint $table) {
            $table->boolean('is_reserved')->default(false)->after('selected_time')->index();

            // Optional: a composite index to speed up the exact match queries
            $table->index(['user_id', 'selected_date', 'selected_time'], 'usd_user_date_time_idx');
        });
    }

    public function down(): void
    {
        Schema::table('user_selected_datetimes', function (Blueprint $table) {
            $table->dropIndex('usd_user_date_time_idx');
            $table->dropColumn('is_reserved');
        });
    }
};
