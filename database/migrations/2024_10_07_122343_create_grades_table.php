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
        Schema::create('grades', function (Blueprint $table) {
            $table->id('grade_id'); // Primary key
            $table->unsignedBigInteger('LRN'); // Learner Reference Number
            $table->unsignedBigInteger('class_id'); // Class ID
            $table->string('grade');
            $table->string('semester')->nullable();
            $table->string('permission')->nullable();
            $table->string('term'); 
            $table->timestamps(); // Created at and updated at timestamps
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
