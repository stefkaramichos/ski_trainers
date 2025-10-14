<?php
// database/migrations/2025_10_13_000000_create_booking_claims_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('booking_claims', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('instructor_id');
            $table->string('token', 80)->unique();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('invalidated_at')->nullable();
            $table->timestamps();

            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            $table->foreign('instructor_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['booking_id', 'instructor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_claims');
    }
};
