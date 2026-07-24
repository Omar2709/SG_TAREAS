<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('created_by')->after('project_id')->constrained('users')->restrictOnDelete();
            $table->date('due_date')->nullable()->after('priority')->index();
            $table->timestamp('completed_at')->nullable()->after('due_date')->index();
            $table->index('status');
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['priority']);
            $table->dropIndex(['due_date']);
            $table->dropIndex(['completed_at']);
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['due_date', 'completed_at']);
        });
    }
};
