<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Columns from the client's "Dog Register" workbook that have no existing
     * home on a dog profile. Values keep the register's own wording.
     *
     * @var list<string>
     */
    private const COLUMNS = [
        'publish_to_bot',
        'adoption_status',
        'foster_status',
        'expected_adult_size',
        'weight_kg',
        'weighed_on',
        'children_assessment',
        'children_observations',
        'dogs_assessment',
        'dogs_observations',
        'cats_assessment',
        'cats_observations',
        'home_requirements',
        'owner_experience',
        'alone_time_observations',
        'house_training',
        'lead_walking',
        'vaccination_status',
        'care_summary',
        'feeding_guidance',
        'register_photo_url',
        'profile_url',
        'last_reviewed_on',
        'reviewed_by',
        'register_imported_at',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dogs', function (Blueprint $table): void {
            $table->boolean('publish_to_bot')->nullable();
            $table->string('adoption_status')->nullable();
            $table->string('foster_status')->nullable();
            $table->string('expected_adult_size')->nullable();
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->date('weighed_on')->nullable();
            $table->string('children_assessment')->nullable();
            $table->text('children_observations')->nullable();
            $table->string('dogs_assessment')->nullable();
            $table->text('dogs_observations')->nullable();
            $table->string('cats_assessment')->nullable();
            $table->text('cats_observations')->nullable();
            $table->text('home_requirements')->nullable();
            $table->string('owner_experience')->nullable();
            $table->text('alone_time_observations')->nullable();
            $table->string('house_training')->nullable();
            $table->string('lead_walking')->nullable();
            $table->string('vaccination_status')->nullable();
            $table->text('care_summary')->nullable();
            $table->text('feeding_guidance')->nullable();
            $table->string('register_photo_url', 2048)->nullable();
            $table->string('profile_url', 2048)->nullable();
            $table->date('last_reviewed_on')->nullable();
            $table->string('reviewed_by')->nullable();
            $table->timestamp('register_imported_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dogs', function (Blueprint $table): void {
            $table->dropColumn(self::COLUMNS);
        });
    }
};
