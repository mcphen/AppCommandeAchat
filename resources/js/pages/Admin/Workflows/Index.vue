<template>
  <PageHeader title="Circuits d'approbation">
    <template #actions>
      <Link class="btn btn-primary" :href="route('admin.workflows.create')">Nouveau circuit</Link>
    </template>
  </PageHeader>
  <div class="mt-4">
    <Table :items="workflows" :fields="fields">
      <template #cell(actions)="{ item }">
        <Link :href="route('admin.workflows.edit', item.id)" class="btn btn-xs btn-warning mr-2">Éditer</Link>
        <Link :href="route('admin.workflows.show', item.id)" class="btn btn-xs btn-info">Voir</Link>
      </template>
    </Table>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import Table from '@/components/Table.vue';

const workflows = ref(usePage().props.workflows || []);
const fields = [
  { key: 'libelle', label: 'Libellé' },
  { key: 'entreprise.nom', label: 'Entreprise' },
  { key: 'categorie_achat', label: 'Catégorie' },
  { key: 'montant_min', label: 'Montant min.' },
  { key: 'montant_max', label: 'Montant max.' },
  { key: 'actions', label: 'Actions' },
];
</script>
