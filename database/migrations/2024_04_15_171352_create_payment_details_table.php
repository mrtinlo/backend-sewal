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
        Schema::create('payment_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('booking_detail_id')->unique();
            $table->unsignedBigInteger('payment_id');
            $table->timestamps();
            $table->softDeletes();

            Schema::disableForeignKeyConstraints();
            $table->foreign('payment_id')->references('id')->on('payments');
            $table->foreign('booking_detail_id')->references('id')->on('booking_details');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_details');
    }
};
