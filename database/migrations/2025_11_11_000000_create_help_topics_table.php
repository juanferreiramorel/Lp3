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
        Schema::create('help_topics', function (Blueprint $table) {
            $table->bigIncrements('id_help');
            $table->string('route_name', 150)->nullable();
            $table->string('permission_slug', 150)->nullable();
            $table->string('module', 100)->nullable();
            $table->string('action_key', 100)->nullable();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('pdf_path', 255);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('route_name', 'idx_help_topics_route_name');
            $table->index('permission_slug', 'idx_help_topics_permission_slug');
            $table->index('module', 'idx_help_topics_module');
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
