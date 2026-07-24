<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
        });

        $usedSlugs = [];

        DB::table('teams')
            ->select(['id', 'name'])
            ->orderBy('id')
            ->get()
            ->each(function (object $team) use (&$usedSlugs): void {
                $baseSlug = Str::slug($team->name) ?: "team-{$team->id}";
                $slug = $baseSlug;
                $suffix = 2;

                while (in_array($slug, $usedSlugs, true)) {
                    $slug = "{$baseSlug}-{$suffix}";
                    $suffix++;
                }

                $usedSlugs[] = $slug;

                DB::table('teams')
                    ->where('id', $team->id)
                    ->update(['slug' => $slug]);
            });

        Schema::table('teams', function (Blueprint $table) {
            $table->unique('slug');
            $table->dropColumn('description');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
