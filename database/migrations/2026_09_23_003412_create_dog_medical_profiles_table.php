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
        Schema::create('dog_medical_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dog_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('spayed_neutered')->nullable();
            $table->date('spayed_neutered_date')->nullable();
            $table->date('last_vet_check_date')->nullable();
            $table->string('veterinary_clinic')->nullable();
            $table->text('initial_health_assessment')->nullable();
            $table->text('medical_concerns')->nullable();
            $table->text('treatment_required')->nullable();
            $table->text('medications')->nullable();
            $table->text('medical_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dog_medical_profiles');
    }
};
