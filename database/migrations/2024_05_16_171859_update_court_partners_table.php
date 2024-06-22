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
        Schema::table('court_partners', function (Blueprint $table) {
            $table->unsignedBigInteger('city_id');
            $table->string('address')->nullable();
            $table->string('google_map')->nullable();

            Schema::disableForeignKeyConstraints();
            $table->foreign('city_id')->references('id')->on('cities');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
