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
        Schema::create('dog_adoption_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dog_id')->constrained()->cascadeOnDelete();
            $table->string('adoption_type');
            $table->string('adopter_name');
            $table->string('adopter_location')->nullable();
            $table->string('adopter_contact')->nullable();
            $table->date('adoption_date')->nullable();
            $table->date('return_date')->nullable();
            $table->text('return_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dog_adoption_records');
    }
};
