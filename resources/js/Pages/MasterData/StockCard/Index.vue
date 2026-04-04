<template>
  <AppLayout>
    <div class="p-6 space-y-6">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <h2 class="text-2xl font-bold">Master Stock Card</h2>
          <p class="text-sm text-slate-400">Kelola master barang non-produk yang dipakai oleh modul Stock Card.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <input
            v-model="filters.q"
            type="text"
            placeholder="Cari nama barang / tipe / code..."
            class="rounded bg-slate-800 px-3 py-2 text-sm text-slate-100"
            @input="onSearchInput"
          />
          <button
            v-if="canManageMaster"
            type="button"
            class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500"
            @click="showCreateModal = true"
          >
            Add Barang
          </button>
        </div>
      </div>

      <div
        v-if="flashSuccess"
        class="rounded border border-emerald-700 bg-emerald-950/40 px-4 py-3 text-sm text-emerald-200"
      >
        {{ flashSuccess }}
      </div>

      <div
        v-if="errorSummary.length"
        class="rounded border border-rose-700 bg-rose-950/40 px-4 py-3 text-sm text-rose-200"
      >
        <div
          v-for="(message, index) in errorSummary"
          :key="`error-${index}`"
        >
          {{ message }}
        </div>
      </div>

      <div class="overflow-auto rounded bg-slate-800 p-4">
        <table class="w-full table-auto text-sm">
          <thead>
            <tr class="text-left text-slate-400">
              <th class="py-2">Code</th>
              <th>Nama Barang</th>
              <th>Jenis / Tipe</th>
              <th>Satuan</th>
              <th>Current Stock</th>
              <th>Min. Stock</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in items"
              :key="item.id"
              class="border-t border-slate-700"
            >
              <td class="py-3 font-medium text-slate-200">{{ item.item_code }}</td>
              <td>{{ item.name }}</td>
              <td>{{ item.item_type }}</td>
              <td>{{ item.unit }}</td>
              <td>{{ item.current_stock }}</td>
              <td>{{ item.minimum_stock }}</td>
              <td>
                <span :class="item.is_active ? 'text-green-400' : 'text-rose-400'">
                  {{ item.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
            </tr>
            <tr v-if="!items.length" class="border-t border-slate-700">
              <td colspan="7" class="py-8 text-center text-slate-400">Belum ada master barang.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div
        v-if="!canManageMaster"
        class="rounded border border-slate-700 bg-slate-800 px-4 py-3 text-sm text-slate-300"
      >
        Anda dapat melihat daftar master barang, tetapi tombol tambah barang hanya muncul untuk user yang memiliki ability
        <span class="font-semibold text-slate-100">manage_master</span> di Access Rules.
      </div>

      <div
        v-if="canManageMaster && (!itemTypes.length || !units.length)"
        class="rounded border border-amber-700 bg-amber-950/40 px-4 py-3 text-sm text-amber-200"
      >
        Silakan lengkapi master <span class="font-semibold">Jenis/Tipe Barang</span> dan
        <span class="font-semibold">Satuan Stock Card</span> terlebih dahulu agar dropdown master barang bisa dipakai.
      </div>
    </div>

    <div
      v-if="showCreateModal && canManageMaster"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4"
    >
      <div class="w-full max-w-xl rounded border border-slate-700 bg-slate-900 p-6 shadow-xl">
        <div class="mb-4 flex items-center justify-between">
          <div>
            <div class="text-lg font-semibold text-slate-100">Add Barang</div>
            <div class="text-sm text-slate-400">Tambahkan master barang untuk kartu stock.</div>
          </div>
          <button type="button" class="text-slate-400 hover:text-slate-200" @click="showCreateModal = false">Tutup</button>
        </div>

        <form class="space-y-4" @submit.prevent="submitMaster">
          <input
            v-model="masterForm.name"
            type="text"
            placeholder="Nama Barang"
            class="w-full rounded border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-100"
          />
          <select
            v-model="masterForm.item_type"
            class="w-full rounded border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-100"
          >
            <option value="">Pilih Jenis / Tipe Barang</option>
            <option
              v-for="itemType in itemTypes"
              :key="itemType"
              :value="itemType"
            >
              {{ itemType }}
            </option>
          </select>
          <div class="grid grid-cols-2 gap-3">
            <select
              v-model="masterForm.unit"
              class="w-full rounded border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-100"
            >
              <option value="">Pilih Satuan</option>
              <option
                v-for="unit in units"
                :key="unit"
                :value="unit"
              >
                {{ unit }}
              </option>
            </select>
            <input
              v-model="masterForm.minimum_stock"
              type="number"
              min="0"
              step="0.01"
              placeholder="Min. Stock"
              class="w-full rounded border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-100"
            />
          </div>
          <textarea
            v-model="masterForm.description"
            rows="3"
            placeholder="Deskripsi"
            class="w-full rounded border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-100"
          ></textarea>

          <div class="flex justify-end gap-2">
            <button
              type="button"
              class="rounded border border-slate-700 px-4 py-2 text-sm text-slate-200"
              @click="showCreateModal = false"
            >
              Batal
            </button>
            <button
              type="submit"
              class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500"
              :disabled="masterForm.processing"
            >
              Simpan
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  items: {
    type: Array,
    default: () => [],
  },
  filters: {
    type: Object,
    default: () => ({
      q: '',
    }),
  },
  canManageMaster: {
    type: Boolean,
    default: false,
  },
  itemTypes: {
    type: Array,
    default: () => [],
  },
  units: {
    type: Array,
    default: () => [],
  },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success || '');
const items = computed(() => props.items || []);
const canManageMaster = computed(() => props.canManageMaster);
const itemTypes = computed(() => props.itemTypes || []);
const units = computed(() => props.units || []);
const errorSummary = computed(() => {
  const errors = page.props.errors || {};
  return Object.values(errors).flat().filter(Boolean);
});

const filters = reactive({
  q: props.filters?.q || '',
});

const showCreateModal = ref(false);

const masterForm = useForm({
  name: '',
  item_type: '',
  unit: '',
  minimum_stock: '',
  description: '',
});

let searchTimer = null;

function fetchList() {
  const query = {};
  if (filters.q) query.q = filters.q;

  router.get('/master-data/stock-card', query, {
    preserveState: true,
    preserveScroll: true,
  });
}

function onSearchInput() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => fetchList(), 300);
}

function submitMaster() {
  masterForm.post('/master-data/stock-card', {
    preserveScroll: true,
    onSuccess: () => {
      masterForm.reset();
      showCreateModal.value = false;
    },
  });
}
</script>
