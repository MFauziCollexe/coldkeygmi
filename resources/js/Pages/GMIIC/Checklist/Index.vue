<template>
  <AppLayout>
    <div class="p-6">
      <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <h2 class="text-2xl font-bold">Checklist</h2>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
          <div class="w-full sm:w-[360px]">
            <SearchableSelect
              v-model="selectedChecklist"
              :options="availableChecklistOptions"
              option-value="id"
              option-label="name"
              placeholder="Pilih Checklist..."
              empty-label="Pilih Checklist"
              input-class="w-full bg-slate-800 text-sm border-slate-700 rounded"
              button-class="border-0 border-l !border-slate-700 rounded-r !bg-slate-800 text-slate-100"
            />
          </div>

          <div class="w-full sm:w-[180px]">
            <input
              v-model="selectedDate"
              type="date"
              class="w-full rounded border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-100"
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
        v-if="!availableChecklistOptions.length"
        class="mb-4 text-sm text-amber-300"
      >
        Belum ada template checklist yang tersedia untuk departement Anda.
      </div>

      <div
        v-if="selectedChecklist && !canOpenCreatePage"
        class="mb-4 text-sm text-amber-300"
      >
        Template detail saat ini baru tersedia untuk checklist `Kotak P3K`, `Kebersihan dan Sanitasi (Non-Warehouse Area)`, `APAR / Smoke Detector / Fire Alarm`, `Pengangkutan Sampah PT SIER`, `Kebersihan dan Sanitasi (Warehouse Area)`, `Personal Hygiene Karyawan`, `Sarana dan Prasarana`, `Patroli Security`, `Site Visit HSE`, dan `Site Visit Maintenance`.
      </div>

      <div class="rounded bg-slate-800 p-4">
        <div
          v-if="canDeleteChecklistEntries && filteredChecklistEntries.length"
          class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
          <div class="text-sm text-slate-400">
            {{ selectedEntryIds.length ? `${selectedEntryIds.length} checklist dipilih.` : 'Centang checklist yang ingin dihapus.' }}
          </div>

          <button
            type="button"
            class="rounded px-4 py-2 text-sm font-semibold transition"
            :class="hasSelectedEntries
              ? 'bg-rose-600 text-white hover:bg-rose-500'
              : 'cursor-not-allowed bg-slate-700 text-slate-400'"
            :disabled="!hasSelectedEntries"
            @click="removeSelectedChecklists"
          >
            Hapus Checklist Terpilih
          </button>
        </div>

        <div v-if="filteredChecklistEntries.length" class="overflow-x-auto">
          <table class="w-full table-auto">
            <thead>
              <tr class="text-left text-slate-400">
                <th v-if="canDeleteChecklistEntries" class="w-12 py-2">
                  <input
                    type="checkbox"
                    class="h-4 w-4 rounded border-slate-500 bg-slate-900 text-rose-500 focus:ring-rose-500"
                    :checked="areAllEntriesSelected"
                    @change="toggleSelectAll($event.target.checked)"
                  />
                </th>
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
                v-for="(entry, index) in filteredChecklistEntries"
                :key="entry.id"
                class="border-t border-slate-700"
              >
                <td v-if="canDeleteChecklistEntries" class="py-3">
                  <input
                    :checked="selectedEntryIds.includes(entry.id)"
                    type="checkbox"
                    class="h-4 w-4 rounded border-slate-500 bg-slate-900 text-rose-500 focus:ring-rose-500"
                    @change="toggleEntrySelection(entry.id, $event.target.checked)"
                  />
                </td>
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
                    class="text-indigo-400 hover:text-indigo-300"
                  >
                    View
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="py-8 text-center text-slate-400">
          Belum ada checklist yang ditambahkan.
        </div>

        <div class="mt-4 text-sm text-slate-400">
          Showing {{ filteredChecklistEntries.length ? 1 : 0 }} to {{ filteredChecklistEntries.length }} of {{ checklistEntries.length }} checklist
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import axios from 'axios';
import { computed, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { swalConfirm } from '@/Utils/swalConfirm';
import { checklistOptions, getChecklistEntryAreaLabel } from './checklistConfig';

const page = usePage();
const selectedChecklist = ref('');
const selectedDate = ref(toDateInputValue(new Date()));
const checklistEntries = ref(Array.isArray(page.props.entries) ? [...page.props.entries] : []);
const selectedEntryIds = ref([]);
const supportedTemplates = ['kotak_p3k', 'non_warehouse_sanitation', 'apar_smoke_detector_fire_alarm', 'pengangkutan_sampah_pt_sier', 'warehouse_sanitation_1', 'personal_hygiene_karyawan', 'sarana_dan_prasarana', 'patroli_security', 'site_visit_hse', 'site_visit_maintenance'];
const checklistAbilities = computed(() => page.props.checklistAbilities || {});
const checklistTemplatePermissions = computed(() => page.props.checklistTemplatePermissions || {});
const availableChecklistOptions = computed(() => {
  return checklistOptions.filter((option) => Boolean(checklistTemplatePermissions.value?.[option.id]?.view));
});
const canDeleteChecklistEntries = computed(() => Boolean(checklistAbilities.value.delete_entries));

const canOpenCreatePage = computed(() => {
  return !selectedChecklist.value
    || (supportedTemplates.includes(selectedChecklist.value) && Boolean(checklistTemplatePermissions.value?.[selectedChecklist.value]?.view));
});

const createChecklistHref = computed(() => {
  if (selectedChecklist.value) {
    return `/gmiic/checklist/create?template=${encodeURIComponent(selectedChecklist.value)}`;
  }

  return '/gmiic/checklist/create';
});

const filteredChecklistEntries = computed(() => {
  return checklistEntries.value.filter((entry) => {
    if (selectedChecklist.value && entry?.template_id !== selectedChecklist.value) {
      return false;
    }

    if (!selectedDate.value) {
      return true;
    }

    return getChecklistEntryDateValue(entry) === selectedDate.value;
  });
});

const hasSelectedEntries = computed(() => selectedEntryIds.value.length > 0);
const areAllEntriesSelected = computed(() => {
  if (!filteredChecklistEntries.value.length) {
    return false;
  }

  return filteredChecklistEntries.value.every((entry) => selectedEntryIds.value.includes(entry.id));
});

watch(filteredChecklistEntries, (entries) => {
  const visibleIds = new Set(entries.map((entry) => entry.id));
  selectedEntryIds.value = selectedEntryIds.value.filter((entryId) => visibleIds.has(entryId));
});

function toggleEntrySelection(id, checked) {
  if (checked) {
    selectedEntryIds.value = Array.from(new Set([...selectedEntryIds.value, id]));
    return;
  }

  selectedEntryIds.value = selectedEntryIds.value.filter((entryId) => entryId !== id);
}

function toggleSelectAll(checked) {
  const visibleIds = filteredChecklistEntries.value.map((entry) => entry.id);

  if (checked) {
    selectedEntryIds.value = Array.from(new Set([...selectedEntryIds.value, ...visibleIds]));
    return;
  }

  selectedEntryIds.value = selectedEntryIds.value.filter((entryId) => !visibleIds.includes(entryId));
}

async function removeSelectedChecklists() {
  const totalSelected = selectedEntryIds.value.length;

  if (!totalSelected) {
    return;
  }

  const ok = await swalConfirm({
    title: 'Hapus Checklist?',
    text: `Hapus ${totalSelected} checklist terpilih?`,
    confirmButtonText: 'Hapus',
    confirmButtonColor: '#dc2626',
  });

  if (!ok) {
    return;
  }

  try {
    await axios.delete('/gmiic/checklist/entries', {
      data: {
        entry_ids: selectedEntryIds.value,
      },
    });
    checklistEntries.value = checklistEntries.value.filter((entry) => !selectedEntryIds.value.includes(entry.id));
    selectedEntryIds.value = [];
  } catch (error) {
    window.alert(error?.response?.data?.message || 'Checklist gagal dihapus.');
  }
}

function getChecklistStatusLabel(entry) {
  if (entry?.template_id === 'non_warehouse_sanitation' && Array.isArray(entry?.form?.submitted_days) && entry.form.submitted_days.length) {
    return 'Waiting HSE';
  }

  if (entry?.template_id === 'kotak_p3k' && Array.isArray(entry?.form?.submitted_months) && entry.form.submitted_months.length) {
    return 'Waiting HSE';
  }

  if (entry?.template_id === 'personal_hygiene_karyawan' && entry?.form?.generated_at && !entry?.form?.approved) {
    return 'Generated';
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
  if (entry?.template_id === 'non_warehouse_sanitation' && Array.isArray(entry?.form?.submitted_days) && entry.form.submitted_days.length) {
    return 'bg-sky-600 text-white';
  }

  if (entry?.template_id === 'kotak_p3k' && Array.isArray(entry?.form?.submitted_months) && entry.form.submitted_months.length) {
    return 'bg-sky-600 text-white';
  }

  if (entry?.template_id === 'personal_hygiene_karyawan' && entry?.form?.generated_at && !entry?.form?.approved) {
    return 'bg-indigo-600 text-white';
  }

  if (entry?.form?.approved) {
    return 'bg-emerald-600 text-white';
  }

  if (entry?.template_id === 'warehouse_sanitation_1' && entry?.form?.verification?.prepared_date) {
    return 'bg-sky-600 text-white';
  }

  return 'bg-amber-600 text-white';
}

function toDateInputValue(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');

  return `${year}-${month}-${day}`;
}

function getChecklistEntryDateValue(entry) {
  const directValue = normalizeIsoDate(entry?.form?.date_value);
  if (directValue) {
    return directValue;
  }

  const displayValue = parseChecklistDisplayDate(entry?.form?.date);
  if (displayValue) {
    return displayValue;
  }

  return null;
}

function normalizeIsoDate(value) {
  const normalized = String(value || '').trim();
  return /^\d{4}-\d{2}-\d{2}$/.test(normalized) ? normalized : null;
}

function parseChecklistDisplayDate(value) {
  const normalized = String(value || '').trim();
  if (!normalized) {
    return null;
  }

  const match = normalized.match(/^(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})$/);
  if (!match) {
    return null;
  }

  const [, dayRaw, monthRaw, yearRaw] = match;
  const monthMap = {
    januari: '01',
    februari: '02',
    maret: '03',
    april: '04',
    mei: '05',
    juni: '06',
    juli: '07',
    agustus: '08',
    september: '09',
    oktober: '10',
    november: '11',
    desember: '12',
  };

  const month = monthMap[String(monthRaw || '').toLowerCase()];
  if (!month) {
    return null;
  }

  const day = String(Number(dayRaw)).padStart(2, '0');
  return `${yearRaw}-${month}-${day}`;
}
</script>
