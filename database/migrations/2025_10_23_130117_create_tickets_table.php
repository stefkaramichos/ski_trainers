<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->string('subject');
            $table->enum('status', ['open','pending','resolved','closed'])->default('open');
            $table->enum('priority', ['low','normal','high'])->default('normal');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['instructor_id','status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
