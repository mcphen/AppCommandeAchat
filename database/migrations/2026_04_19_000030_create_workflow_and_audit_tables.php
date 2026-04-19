<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Table: approval_workflows
        Schema::create('approval_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained()->onDelete('cascade');
            $table->string('categorie_achat')->nullable();
            $table->decimal('montant_min', 15, 2)->nullable();
            $table->decimal('montant_max', 15, 2)->nullable();
            $table->string('libelle');
            $table->timestamps();
        });

        // Table: approval_steps
        Schema::create('approval_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_workflow_id')->constrained()->onDelete('cascade');
            $table->integer('ordre');
            $table->foreignId('validateur_id')->constrained('validateurs');
            $table->integer('delai_jours')->nullable(); // SLA
            $table->timestamps();
        });

        // Table: delegations
        Schema::create('delegations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delegant_id')->constrained('users');
            $table->foreignId('delegataire_id')->constrained('users');
            $table->dateTime('date_debut');
            $table->dateTime('date_fin');
            $table->string('scope')->nullable(); // Optionnel: catégorie, entreprise, etc.
            $table->timestamps();
        });

        // Table: escalades
        Schema::create('escalades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_step_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users'); // Vers qui escalader
            $table->integer('delai_jours'); // Après combien de jours
            $table->timestamps();
        });

        // Table: audits
        Schema::create('audits', function (Blueprint $table) {
            $table->id();
            $table->string('action');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->json('avant')->nullable();
            $table->json('apres')->nullable();
            $table->timestamps();
        });

        // Table: versions
        Schema::create('versions', function (Blueprint $table) {
            $table->id();
            $table->string('versionable_type');
            $table->unsignedBigInteger('versionable_id');
            $table->integer('numero');
            $table->json('data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('versions');
        Schema::dropIfExists('audits');
        Schema::dropIfExists('escalades');
        Schema::dropIfExists('delegations');
        Schema::dropIfExists('approval_steps');
        Schema::dropIfExists('approval_workflows');
    }
};
