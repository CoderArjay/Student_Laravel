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
        Schema::create('tuition_and_fees', function (Blueprint $table) {
            $table->id('fee_id'); // Primary key
            $table->string('grade_level'); // Year level as a string
            $table->decimal('tuition', 10, 2); // Tuition fee with precision and scale
            $table->decimal('general', 10, 2); // General fee with precision and scale
            $table->decimal('esc', 10, 2)->nullable(); // ESC fee, nullable
            $table->decimal('subsidy', 10, 2)->nullable(); // Subsidy fee, nullable
            $table->decimal('req_downpayment', 10, 2)->nullable(); // Required downpayment, nullable
            $table->timestamps(); // Created at and updated at timestamps
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tuition_and_fees');
    }
};
