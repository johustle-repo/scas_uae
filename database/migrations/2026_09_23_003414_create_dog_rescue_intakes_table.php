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
        Schema::create('dog_rescue_intakes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dog_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('date_received')->nullable();
            $table->string('rescue_location')->nullable();
            $table->text('how_found')->nullable();
            $table->text('initial_condition')->nullable();
            $table->string('source_of_intake')->nullable();
            $table->text('previous_owner_surrender_details')->nullable();
            $table->date('intake_date')->nullable();
            $table->text('intake_notes')->nullable();
            $table->text('rescue_story')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dog_rescue_intakes');
    }
};
