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
        Schema::create('booking_details', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('court_id');
            $table->unsignedInteger('is_membership');
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('discount');
            $table->boolean('is_paid')->default(false);
            $table->softDeletes();

            Schema::disableForeignKeyConstraints();
            $table->foreign('court_id')->references('id')->on('courts');
            $table->foreign('booking_id')->references('id')->on('bookings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_details');
    }
};
