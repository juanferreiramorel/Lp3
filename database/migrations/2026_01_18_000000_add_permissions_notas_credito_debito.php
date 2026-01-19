<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            'notas_credito index',
            'notas_credito create',
            'notas_credito edit',
            'notas_credito destroy',
            'notas_debito index',
            'notas_debito create',
            'notas_debito edit',
            'notas_debito destroy',
        ];

        $now = Carbon::now();

        foreach ($permissions as $permission) {
            // Check if permission already exists to avoid duplicates
            $exists = DB::table('permissions')->where('name', $permission)->where('guard_name', 'web')->exists();
            
            if (!$exists) {
                DB::table('permissions')->insert([
                    'name' => $permission,
                    'guard_name' => 'web',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissions = [
            'notas_credito index',
            'notas_credito create',
            'notas_credito edit',
            'notas_credito destroy',
            'notas_debito index',
            'notas_debito create',
            'notas_debito edit',
            'notas_debito destroy',
        ];

        DB::table('permissions')->whereIn('name', $permissions)->delete();
    }
};
