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
        Schema::table('nature_operations', function (Blueprint $table) {
            $table->string('slug')->default('')->after('name');
        });

        $usedSlugs = [];

        foreach (DB::table('nature_operations')->orderBy('id')->get(['id', 'name']) as $natureOperation) {
            $baseSlug = Str::slug($natureOperation->name);

            if ($baseSlug === '') {
                $baseSlug = (string) Str::of($natureOperation->name)
                    ->trim()
                    ->lower()
                    ->replaceMatches('/\s+/', '-');
            }

            $slug = $baseSlug;

            if ($slug === '' || isset($usedSlugs[$slug])) {
                $slug = ($baseSlug !== '' ? $baseSlug : 'nature-operation') . '-' . $natureOperation->id;
            }

            $usedSlugs[$slug] = true;

            DB::table('nature_operations')
                ->where('id', $natureOperation->id)
                ->update(['slug' => $slug]);
        }

        Schema::table('nature_operations', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('nature_operations', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};