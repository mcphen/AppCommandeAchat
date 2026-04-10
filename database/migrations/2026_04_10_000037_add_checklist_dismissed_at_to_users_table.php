<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('checklist_dismissed_at')->nullable()->after('onboarding_completed_at')
                ->comment('Date à laquelle l\'admin a fermé définitivement la checklist d\'activation');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('checklist_dismissed_at');
        });
    }
};
