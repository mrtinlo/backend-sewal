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
        Schema::create('court_partner_facilities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('court_partner_id');
            $table->unsignedBigInteger('facility_id');
            $table->timestamps();

            Schema::disableForeignKeyConstraints();
            $table->foreign('court_partner_id')->references('id')->on('court_partners');
            $table->foreign('facility_id')->references('id')->on('facilities');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('court_partner_facilites');
    }
};
