<template>
  <AppLayout>
    <div class="p-6">
      <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <h2 class="text-2xl font-bold">Checklist</h2>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
          <div class="w-full sm:w-[360px]">
            <SearchableSelect
              v-model="selectedChecklist"
              :options="checklistOptions"
              option-value="id"
              option-label="name"
              placeholder="Pilih Checklist..."
              empty-label="Pilih Checklist"
              input-class="w-full bg-slate-800 text-sm border-slate-700 rounded"
              button-class="border-0 border-l !border-slate-700 rounded-r !bg-slate-800 text-slate-100"
            />
          </div>

          <Link
            :href="createChecklistHref"
            class="rounded bg-indigo-600 px-4 py-2 text-center text-sm font-semibold text-white transition hover:bg-indigo-500"
            :class="!canOpenCreatePage ? 'pointer-events-none bg-slate-700 text-slate-400' : ''"
          >
            New Checklist
          </Link>
        </div>
      </div>

      <div
        v-if="selectedChecklist && !canOpenCreatePage"
        class="mb-4 text-sm text-amber-300"
      >
        Template detail saat ini baru tersedia untuk checklist `Kotak P3K`, `Kebersihan dan Sanitasi (Non-Warehouse Area)`, `Pengangkutan Sampah PT SIER`, `Kebersihan dan Sanitasi (Warehouse Area)`, dan `Personal Hygiene Karyawan`.
      </div>

      <div class="rounded bg-slate-800 p-4">
        <div v-if="checklistEntries.length" class="overflow-x-auto">
          <table class="w-full table-auto">
            <thead>
              <tr class="text-left text-slate-400">
                <th class="py-2">#</th>
                <th>Checklist</th>
                <th>Lokasi / Area</th>
                <th>Date</th>
                <th>PIC</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(entry, index) in checklistEntries"
                :key="entry.id"
                class="border-t border-slate-700"
              >
                <td class="py-3">{{ index + 1 }}</td>
                <td>{{ entry.name }}</td>
                <td>{{ getChecklistEntryAreaLabel(entry) }}</td>
                <td>{{ entry.form?.date || entry.form?.period || '-' }}</td>
                <td>{{ entry.form?.pic || '-' }}</td>
                <td>
                  <span
                    class="inline-flex rounded px-2 py-1 text-xs font-semibold"
                    :class="getChecklistStatusClass(entry)"
                  >
                    {{ getChecklistStatusLabel(entry) }}
                  </span>
                </td>
                <td class="text-right">
                  <Link
                    :href="`/gmiic/checklist/create?template=${encodeURIComponent(entry.template_id)}&entry_id=${encodeURIComponent(entry.id)}`"
                    class="mr-3 text-indigo-400 hover:text-indigo-300"
                  >
                    View
                  </Link>
                  <button
                    type="button"
                    class="text-rose-400 hover:text-rose-300"
                    @click="removeChecklist(entry.id)"
                  >
                    Remove
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="py-8 text-center text-slate-400">
          Belum ada checklist yang ditambahkan.
        </div>

        <div class="mt-4 text-sm text-slate-400">
          Showing {{ checklistEntries.length ? 1 : 0 }} to {{ checklistEntries.length }} of {{ checklistEntries.length }} checklist
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { checklistOptions, getChecklistEntryAreaLabel } from './checklistConfig';
import { loadChecklistEntries, saveChecklistEntries } from './checklistStorage';

const selectedChecklist = ref('');
const checklistEntries = ref([]);
const supportedTemplates = ['kotak_p3k', 'non_warehouse_sanitation', 'pengangkutan_sampah_pt_sier', 'warehouse_sanitation_1', 'personal_hygiene_karyawan'];

const canOpenCreatePage = computed(() => {
  return !selectedChecklist.value || supportedTemplates.includes(selectedChecklist.value);
});

const createChecklistHref = computed(() => {
  if (selectedChecklist.value) {
    return `/gmiic/checklist/create?template=${encodeURIComponent(selectedChecklist.value)}`;
  }

  return '/gmiic/checklist/create';
});

function removeChecklist(id) {
  checklistEntries.value = checklistEntries.value.filter((entry) => entry.id !== id);
  saveChecklistEntries(checklistEntries.value);
}

function getChecklistStatusLabel(entry) {
  if (entry?.template_id === 'kotak_p3k' && Array.isArray(entry?.form?.submitted_months) && entry.form.submitted_months.length) {
    return 'Waiting HSE';
  }

  if (entry?.form?.approved) {
    return 'Approved';
  }

  if (entry?.template_id === 'warehouse_sanitation_1' && entry?.form?.verification?.prepared_date) {
    return 'Waiting Manager';
  }

  return 'Draft';
}

function getChecklistStatusClass(entry) {
  if (entry?.template_id === 'kotak_p3k' && Array.isArray(entry?.form?.submitted_months) && entry.form.submitted_months.length) {
    return 'bg-sky-600 text-white';
  }

  if (entry?.form?.approved) {
    return 'bg-emerald-600 text-white';
  }

  if (entry?.template_id === 'warehouse_sanitation_1' && entry?.form?.verification?.prepared_date) {
    return 'bg-sky-600 text-white';
  }

  return 'bg-amber-600 text-white';
}

onMounted(() => {
  checklistEntries.value = loadChecklistEntries();
});
</script>
