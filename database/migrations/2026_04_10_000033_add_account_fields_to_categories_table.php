<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('account_code', 10)->nullable()->after('is_active')
                ->comment('Code comptable OHADA/SYSCOHADA (ex: 60400)');
            $table->string('account_label', 255)->nullable()->after('account_code')
                ->comment('Libellé du compte comptable');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['account_code', 'account_label']);
        });
    }
};
