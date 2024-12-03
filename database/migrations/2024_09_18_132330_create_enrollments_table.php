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
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id('enrol_id');  // Creates an auto-incrementing ID column
            $table->unsignedBigInteger('lrn');  // Logical Record Number
            $table->timestamp('regapproval_date')->nullable();  // Custom timestamp for registration approval
            $table->timestamp('payment_approval')->nullable();  // Custom timestamp for payment approval
            $table->string('grade_level');  // Year level as a string
            $table->string('guardian_name');  // Guardian's name as a string
            $table->string('last_attended');  // Last attended school or class as a string
            $table->string('public_private');  // Public or private indication as a string
            $table->timestamp('date_register')->nullable();  // Custom timestamp for registration date
            $table->string('strand')->nullable();  // Educational strand as a string
            $table->string('school_year');  // School year as a string
            $table->timestamps();  // Creates created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
