<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('team_user', 'team_members');

        Schema::table('team_members', function (Blueprint $table) {
            $table->timestamp('joined_at')->nullable()->after('role');
            $table->unique(['team_id', 'user_id']);
            $table->index('role');
        });

        DB::table('team_members')
            ->whereNull('joined_at')
            ->update(['joined_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)')]);
    }

    public function down(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->dropUnique(['team_id', 'user_id']);
            $table->dropIndex(['role']);
            $table->dropColumn('joined_at');
        });

        Schema::rename('team_members', 'team_user');
    }
};
