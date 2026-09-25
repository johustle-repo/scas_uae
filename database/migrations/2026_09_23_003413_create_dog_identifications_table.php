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
        Schema::create('dog_identifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dog_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('microchip_number')->nullable()->unique();
            $table->date('microchip_date')->nullable();
            $table->string('microchip_location')->nullable();
            $table->string('microchip_registration_status')->nullable();
            $table->string('passport_number')->nullable();
            $table->date('passport_issue_date')->nullable();
            $table->date('passport_expiry_date')->nullable();
            $table->string('passport_issuing_authority')->nullable();
            $table->string('passport_status')->nullable();
            $table->string('pcc_number')->nullable();
            $table->date('pcc_registration_date')->nullable();
            $table->string('pcc_registration_status')->nullable();
            $table->text('supporting_documents_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dog_identifications');
    }
};
