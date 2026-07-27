<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('projects')) {
            Schema::create('projects', function (Blueprint $table) {
                $table->id();
                $table->foreignId('team_id')->constrained()->cascadeOnDelete();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('status')->default('active');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });

            return;
        }

        if (! Schema::hasColumn('projects', 'created_by')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->foreignId('created_by')->nullable()->after('team_id')->constrained('users')->cascadeOnDelete();
            });
        }

        if (! Schema::hasColumn('projects', 'status')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->string('status')->default('active')->after('description');
            });
        }

        if (! Schema::hasColumn('projects', 'started_at')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->timestamp('started_at')->nullable()->after('status');
            });
        }

        if (! Schema::hasColumn('projects', 'completed_at')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->timestamp('completed_at')->nullable()->after('started_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
