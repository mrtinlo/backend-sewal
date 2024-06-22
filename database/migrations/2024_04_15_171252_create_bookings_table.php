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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->date('date');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('court_partner_id');
            $table->text('booking_id');
            $table->integer('is_membership')->default(0);
            $table->unsignedBigInteger('total_payment');
            $table->unsignedBigInteger('total_discount');
            $table->enum('payment_type',['full-payment','down-payment','no-payment']);
            $table->boolean('is_paid')->default(0);
            $table->softDeletes();

            Schema::disableForeignKeyConstraints();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('court_partner_id')->references('id')->on('court_partners');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
