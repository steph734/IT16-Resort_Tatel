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
        Schema::create('amenity_package', function (Blueprint $table) {
            // package primary key is a string PackageID
            $table->string('package_id', 10);
            $table->unsignedBigInteger('amenity_id');

            // Composite primary to prevent duplicates
            $table->primary(['package_id', 'amenity_id']);

            // Foreign keys
            $table->foreign('package_id')->references('PackageID')->on('packages')->onDelete('cascade');
            $table->foreign('amenity_id')->references('id')->on('amenities')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amenity_package');
    }
};
