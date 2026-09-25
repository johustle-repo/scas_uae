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
        Schema::create('dogs', function (Blueprint $table): void {
            $table->id();
            $table->string('scas_id')->unique();
            $table->unsignedInteger('legacy_source_row')->nullable()->unique();
            $table->string('name');
            $table->string('species')->default('canine');
            $table->string('breed')->nullable();
            $table->string('colour_markings')->nullable();
            $table->string('gender')->nullable();
            $table->string('size')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->boolean('date_of_birth_is_approximate')->default(false);
            $table->string('estimated_age_notes')->nullable();
            $table->string('current_status')->default('at_scas');
            $table->string('current_location')->nullable();
            $table->string('photo_path')->nullable();
            $table->text('personality_description')->nullable();
            $table->string('energy_level')->nullable();
            $table->text('temperament')->nullable();
            $table->boolean('good_with_dogs')->nullable();
            $table->boolean('good_with_cats')->nullable();
            $table->boolean('good_with_children')->nullable();
            $table->boolean('good_with_adults')->nullable();
            $table->string('training_level')->nullable();
            $table->boolean('potty_trained')->nullable();
            $table->boolean('leash_trained')->nullable();
            $table->text('basic_commands_notes')->nullable();
            $table->text('behavioural_notes')->nullable();
            $table->text('special_requirements')->nullable();
            $table->timestamps();

            $table->index('current_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dogs');
    }
};
