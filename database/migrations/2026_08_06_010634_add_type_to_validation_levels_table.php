<?php

use App\Models\ValidationLevel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('validation_levels', function (Blueprint $table) {
            $table->string('type')->default('validation')->after('name');
        });

        // Insère un niveau "Approbation" (DGA) juste avant le dernier niveau existant du circuit,
        // quel que soit son order/nom actuel : décale le dernier niveau d'un cran et prend sa place.
        DB::transaction(function () {
            $lastLevel = ValidationLevel::orderByDesc('order')->first();

            if (! $lastLevel) {
                return;
            }

            $insertedOrder = $lastLevel->order;

            $lastLevel->update(['order' => $lastLevel->order + 1]);

            ValidationLevel::create([
                'name'  => 'DGA',
                'order' => $insertedOrder,
                'type'  => 'approbation',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::transaction(function () {
            $dgaLevel = ValidationLevel::where('type', 'approbation')->orderByDesc('order')->first();

            if ($dgaLevel) {
                $nextLevel = ValidationLevel::where('order', '>', $dgaLevel->order)->orderBy('order')->first();

                if ($nextLevel) {
                    $nextLevel->update(['order' => $dgaLevel->order]);
                }

                $dgaLevel->delete();
            }
        });

        Schema::table('validation_levels', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
