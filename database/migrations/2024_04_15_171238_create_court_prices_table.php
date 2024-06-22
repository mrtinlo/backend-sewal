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
        Schema::create('court_prices', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('court_id');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('day',['monday','tuesday','wednesday','thursday','friday','saturday','sunday']);
            $table->bigInteger('price')->nullable();

            Schema::disableForeignKeyConstraints();
            $table->foreign('court_id')->references('id')->on('courts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('court_prices');
    }
};
