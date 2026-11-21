<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The Dog Register's "Microchip status" (Yes / No / Unknown), kept so an
     * export reproduces it even when no chip number is on file.
     */
    public function up(): void
    {
        Schema::table('dogs', function (Blueprint $table): void {
            $table->string('microchip_status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dogs', function (Blueprint $table): void {
            $table->dropColumn('microchip_status');
        });
    }
};
