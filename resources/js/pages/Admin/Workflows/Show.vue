<template>
  <PageHeader :title="'Circuit : ' + workflow.libelle">
    <template #actions>
      <Link class="btn btn-secondary" :href="route('admin.workflows.index')">Retour</Link>
      <Link class="btn btn-warning" :href="route('admin.workflows.edit', workflow.id)">Éditer</Link>
    </template>
  </PageHeader>
  <div class="mt-4">
    <div><b>Entreprise :</b> {{ workflow.entreprise.nom }}</div>
    <div><b>Catégorie :</b> {{ workflow.categorie_achat }}</div>
    <div><b>Montant min. :</b> {{ workflow.montant_min }}</div>
    <div><b>Montant max. :</b> {{ workflow.montant_max }}</div>
    <div class="mt-4">
      <h3 class="font-bold mb-2">Étapes d'approbation</h3>
      <ol>
        <li v-for="step in workflow.steps" :key="step.id">
          {{ step.ordre }}. {{ step.validateur.nom }} <span v-if="step.delai_jours">(SLA : {{ step.delai_jours }} j)</span>
        </li>
      </ol>
    </div>
  </div>
</template>

<script setup>
import { usePage, Link } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';

const workflow = usePage().props.workflow;
</script>
