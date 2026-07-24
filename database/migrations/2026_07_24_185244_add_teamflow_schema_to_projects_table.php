<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('created_by')->after('team_id')->constrained('users')->restrictOnDelete();
            $table->string('status')->default('active')->after('description')->index();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn('status');
        });
    }
};
