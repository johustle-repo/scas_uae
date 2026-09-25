<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('records_manager')->change();
            $table->boolean('is_active')->default(true)->after('role');
        });

        DB::table('users')->where('role', 'staff')->update(['role' => 'records_manager']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')->where('role', '!=', 'admin')->update(['role' => 'staff']);

        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('staff')->change();
            $table->dropColumn('is_active');
        });
    }
};
