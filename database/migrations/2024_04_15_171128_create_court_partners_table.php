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
        Schema::create('court_partners', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->time('start_at');
            $table->time('end_at');
            $table->boolean('is_down_payment')->default(false);
            $table->enum('membership_type',['byfour','monthly']);
            $table->string('bank_account_name')->default(null);
            $table->string('bank_account_number')->default(null);
            $table->string('pin');
            $table->unsignedInteger('down_payment_percentage')->default(50);
            $table->unsignedBigInteger('down_payment_amount')->default(0);
            $table->enum('down_payment_type',['percentage','amount'])->default('percentage');
            $table->boolean('whatsapp_notification')->default(1);
            $table->boolean('is_keep')->default(0);
            $table->string('profile')->nullable();
            $table->softDeletes();

            $table->timestamps();

            Schema::disableForeignKeyConstraints();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('CASCADE')->onUpdate('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('court_partners');
    }
};
