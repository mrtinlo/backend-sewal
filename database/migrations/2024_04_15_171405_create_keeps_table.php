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
        Schema::create('keeps', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->date('date');
            $table->unsignedBigInteger('court_partner_id');
            $table->text('keep_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('is_membership')->default(0);

            Schema::disableForeignKeyConstraints();
            $table->foreign('court_partner_id')->references('id')->on('court_partners');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keeps');
    }
};
