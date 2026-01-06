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
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_url')->nullable()->after('email');
            $table->string('shortcut_mode')->default('automatic')->after('avatar_url'); // 'automatic' or 'manual'
            $table->text('shortcuts')->nullable()->after('shortcut_mode'); // JSON of selected routes for manual mode
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar_url', 'shortcut_mode', 'shortcuts']);
        });
    }
};
