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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->text('payment_id');
            $table->enum('payment_method',['cash','transfer','-'])->default('-');
            $table->unsignedBigInteger('amount')->default(0);
            $table->unsignedbigInteger('booking_id');
            $table->enum('type',['down-payment','schedule']);
            $table->text('payment_link')->default('-');
            $table->softDeletes();

            Schema::disableForeignKeyConstraints();
            $table->foreign('booking_id')->references('id')->on('bookings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
