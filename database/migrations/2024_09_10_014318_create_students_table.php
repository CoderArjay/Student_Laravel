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
        Schema::create('students', function (Blueprint $table) {
            $table->id('LRN'); 
            $table->string('fname', 255); 
            $table->string('lname', 255);
            $table->string('mname', 255)->nullable();
            $table->string('suffix', 25)->nullable();
            $table->date('bdate')->nullable();
            $table->string('bplace', 255)->nullable();
            $table->string('gender', 255)->nullable();
            $table->string('religion', 255)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('contact_no', 15)->nullable();
            $table->string('email', 255)->unique();
            $table->string('password', 255);
            $table->string('profile', 255);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }

};
