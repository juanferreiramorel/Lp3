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
        Schema::create('help_topics', function (Blueprint $table) {
            $table->increments('id_help');
            $table->string('route_name', 150)->nullable()->index('idx_help_topics_route_name');
            $table->string('permission_slug', 150)->nullable()->index('idx_help_topics_permission_slug');
            $table->string('module', 100)->nullable()->index('idx_help_topics_module');
            $table->string('action_key', 100)->nullable();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('pdf_path');
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->nullable()->default(DB::raw("now()"));
            $table->timestamp('updated_at')->nullable()->default(DB::raw("now()"));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('help_topics');
    }
};
