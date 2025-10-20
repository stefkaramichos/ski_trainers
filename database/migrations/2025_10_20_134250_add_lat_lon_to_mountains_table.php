<?php
// database/migrations/2025_10_20_000000_add_lat_lon_to_mountains_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('mountains', function (Blueprint $table) {
            // 10,7 is common (±DDD.dddddd7)
            $table->decimal('latitude', 10, 7)->nullable()->after('mountain_name');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }
    public function down(): void {
        Schema::table('mountains', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
