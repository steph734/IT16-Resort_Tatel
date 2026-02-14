<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->string('PackageID', 10)->primary();
            $table->string('Name', 50);
            $table->text('Description');
            $table->decimal('Price', 10, 2);
            $table->unsignedInteger('max_guests')->default(0);
            $table->unsignedInteger('excess_rate')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
