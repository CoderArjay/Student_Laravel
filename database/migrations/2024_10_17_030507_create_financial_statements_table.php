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
    Schema::create('financial_statements', function (Blueprint $table) {
        $table->id('soa_id'); // Primary key
        $table->unsignedBigInteger('LRN'); // Learner Reference Number
        $table->string('filename'); // Name of the file
        $table->date('date_uploaded');
        $table->timestamps(); // Created at and updated at timestamps
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_statements');
    }
};
