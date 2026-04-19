<template>
  <PageHeader :title="form.id ? 'Éditer un circuit' : 'Nouveau circuit'">
    <template #actions>
      <Link class="btn btn-secondary" :href="route('admin.workflows.index')">Retour</Link>
    </template>
  </PageHeader>
  <form @submit.prevent="submit">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
      <div>
        <label>Libellé</label>
        <input v-model="form.libelle" class="input input-bordered w-full" required />
      </div>
      <div>
        <label>Entreprise</label>
        <select v-model="form.entreprise_id" class="input input-bordered w-full" required>
          <option v-for="e in entreprises" :key="e.id" :value="e.id">{{ e.nom }}</option>
        </select>
      </div>
      <div>
        <label>Catégorie d'achat</label>
        <input v-model="form.categorie_achat" class="input input-bordered w-full" />
      </div>
      <div>
        <label>Montant min.</label>
        <input v-model="form.montant_min" type="number" class="input input-bordered w-full" />
      </div>
      <div>
        <label>Montant max.</label>
        <input v-model="form.montant_max" type="number" class="input input-bordered w-full" />
      </div>
    </div>
    <div class="mt-6">
      <h3 class="font-bold mb-2">Étapes d'approbation</h3>
      <ApprovalStepsEditor v-model:steps="form.steps" :validateurs="validateurs" />
    </div>
    <div class="mt-6 flex gap-2">
      <button class="btn btn-primary" type="submit">Enregistrer</button>
      <Link class="btn btn-secondary" :href="route('admin.workflows.index')">Annuler</Link>
    </div>
  </form>
</template>

<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import ApprovalStepsEditor from '@/components/ApprovalStepsEditor.vue';

const entreprises = ref(usePage().props.entreprises || []);
const validateurs = ref(usePage().props.validateurs || []);
const form = ref(usePage().props.form || { steps: [] });

function submit() {
  // À compléter avec Inertia post/put
}
</script>
