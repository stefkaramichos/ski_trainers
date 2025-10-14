<?php
// database/migrations/2025_10_13_000001_create_bookings_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            // The instructor being booked (the user in /profile/{user})
            $table->foreignId('instructor_id')->constrained('users')->onDelete('cascade');

            // Optional mountain selection
            $table->foreignId('mountain_id')->nullable()->constrained('mountains')->nullOnDelete();

            // Customer info (can be guest)
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();

            // Slot
            $table->date('selected_date');
            $table->time('selected_time');

            // Extras
            $table->unsignedTinyInteger('people_count')->default(1);
            $table->string('level')->nullable(); // e.g. "Beginner/Intermediate/Advanced"
            $table->text('notes')->nullable();

            // Status: pending, confirmed, canceled
            $table->string('status')->default('pending');

            $table->timestamps();

            // Prevent double-booking this instructor at the same datetime (unless old booking is canceled)
            $table->unique(['instructor_id', 'selected_date', 'selected_time'], 'unique_instructor_slot');
        });
    }

    public function down(): void {
        Schema::dropIfExists('bookings');
    }
};
