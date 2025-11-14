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
        if (!Schema::hasTable('productos')) {
            return;
        }

        Schema::table('productos', function (Blueprint $table) {
            if (!Schema::hasColumn('productos', 'controlar_stock')) {
                $table->boolean('controlar_stock')->default(true)->after('tipo_iva');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('productos')) {
            return;
        }

        Schema::table('productos', function (Blueprint $table) {
            if (Schema::hasColumn('productos', 'controlar_stock')) {
                $table->dropColumn('controlar_stock');
            }
        });
    }
};
