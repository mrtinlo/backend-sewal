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
        Schema::create('courts', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('status')->default(1);
            $table->unsignedBigInteger('court_partner_id');
            $table->unsignedBigInteger('court_type_id');
            $table->softDeletes();

            Schema::disableForeignKeyConstraints();
            $table->foreign('court_type_id')->references('id')->on('court_types')->onDelete('CASCADE')->onUpdate('CASCADE');
            $table->foreign('court_partner_id')->references('id')->on('court_partners');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courts');
    }
};
