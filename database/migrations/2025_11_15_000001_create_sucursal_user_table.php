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
        if (!Schema::hasTable('sucursal_user')) {
            Schema::create('sucursal_user', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id');
                $table->unsignedInteger('id_sucursal');

                $table->primary(['user_id', 'id_sucursal']);

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('id_sucursal')->references('id_sucursal')->on('sucursales')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sucursal_user');
    }
};
