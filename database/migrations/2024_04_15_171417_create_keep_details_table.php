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
        Schema::create('keep_details', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedBigInteger('court_id');
            $table->unsignedBigInteger('keep_id');
            $table->unsignedInteger('is_membership')->default(0);

            Schema::disableForeignKeyConstraints();
            $table->foreign('court_id')->references('id')->on('courts');
            $table->foreign('keep_id')->references('id')->on('keeps');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keep_details');
    }
};
