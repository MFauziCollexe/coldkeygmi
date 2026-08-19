<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="text-2xl font-bold">Tally</h2>
          <p class="text-sm text-slate-400">Daftar data tally / Add PO.</p>
        </div>
        <div class="flex items-center gap-2">
          <button
            type="button"
            @click="deleteTally"
            :disabled="!selectedId"
            class="inline-flex items-center justify-center rounded bg-rose-600 px-4 py-2 text-white hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50"
          >
            Delete Tally
          </button>
          <button
            type="button"
            @click="deletePo"
            :disabled="!selectedId"
            class="inline-flex items-center justify-center rounded bg-rose-700 px-4 py-2 text-white hover:bg-rose-800 disabled:cursor-not-allowed disabled:opacity-50"
          >
            Delete PO
          </button>
          <button
            type="button"
            @click="openTallyModal"
            :disabled="!selectedId || isPoFinished"
            class="inline-flex items-center justify-center rounded bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
          >
            Add Tally
          </button>
          <button
            type="button"
            @click="openModal"
            class="inline-flex items-center justify-center rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700"
          >
            Add PO
          </button>
        </div>
      </div>

      <div
        v-if="flashMessage"
        class="mb-4 rounded border border-emerald-300 bg-emerald-50 p-3 text-sm text-emerald-800"
      >
        {{ flashMessage }}
      </div>

      <div v-if="tallies.length === 0" class="rounded-md border border-slate-200 bg-white p-10 text-center text-slate-400 shadow-sm">
        Belum ada data tally. Klik Add PO untuk menambahkan.
      </div>

      <div v-else class="overflow-x-auto rounded-md border border-slate-200 bg-white shadow-sm">
        <table class="w-full border-collapse text-sm">
          <thead>
            <tr class="bg-slate-50 text-left text-slate-500">
              <th class="whitespace-nowrap border-b border-slate-200 px-4 py-2 font-semibold">Pilih</th>
              <th class="whitespace-nowrap border-b border-slate-200 px-4 py-2 font-semibold">#</th>
              <th class="whitespace-nowrap border-b border-slate-200 px-4 py-2 font-semibold">PO</th>
              <th class="whitespace-nowrap border-b border-slate-200 px-4 py-2 font-semibold">Nopol</th>
              <th class="whitespace-nowrap border-b border-slate-200 px-4 py-2 font-semibold">Driver</th>
              <th class="whitespace-nowrap border-b border-slate-200 px-4 py-2 font-semibold">Customer</th>
              <th class="whitespace-nowrap border-b border-slate-200 px-4 py-2 font-semibold">Transaksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(tally, index) in tallies" :key="tally.id" class="text-slate-800 hover:bg-slate-50">
              <td class="border-b border-slate-100 px-4 py-2 text-center">
                <input
                  type="checkbox"
                  :checked="selectedId === tally.id"
                  @change="toggleSelect(tally.id)"
                  class="h-4 w-4 cursor-pointer rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                />
              </td>
              <td class="whitespace-nowrap border-b border-slate-100 px-4 py-2">{{ index + 1 }}</td>
              <td class="whitespace-nowrap border-b border-slate-100 px-4 py-2">{{ tally.po || '-' }}</td>
              <td class="whitespace-nowrap border-b border-slate-100 px-4 py-2">{{ tally.nopol || '-' }}</td>
              <td class="whitespace-nowrap border-b border-slate-100 px-4 py-2">{{ tally.driver || '-' }}</td>
              <td class="whitespace-nowrap border-b border-slate-100 px-4 py-2">{{ tally.customer?.name || '-' }}</td>
              <td class="whitespace-nowrap border-b border-slate-100 px-4 py-2">{{ tally.transaksi || '-' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Add PO -->
    <div
      v-if="showModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
      @click.self="closeModal"
    >
      <div class="w-full max-w-md overflow-hidden rounded-xl border border-slate-300 bg-white p-5 shadow-2xl">
        <div class="mb-4 flex items-center justify-between gap-4">
          <h3 class="text-base font-semibold text-black">Tambah PO</h3>
          <button type="button" class="text-slate-400 hover:text-slate-600" @click="closeModal">
            &times;
          </button>
        </div>

        <form @submit.prevent="submitForm" class="space-y-4">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700" for="po">PO</label>
            <input
              id="po"
              v-model="form.po"
              type="text"
              required
              placeholder="Isi nomor PO"
              class="w-full rounded border border-slate-300 bg-transparent px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            />
            <p v-if="form.errors.po" class="mt-1 text-xs text-rose-600">{{ form.errors.po }}</p>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700" for="nopol">Nopol</label>
            <input
              id="nopol"
              v-model="form.nopol"
              type="text"
              required
              placeholder="Isi nomor polisi"
              class="w-full rounded border border-slate-300 bg-transparent px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            />
            <p v-if="form.errors.nopol" class="mt-1 text-xs text-rose-600">{{ form.errors.nopol }}</p>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700" for="driver">Driver</label>
            <input
              id="driver"
              v-model="form.driver"
              type="text"
              required
              placeholder="Nama driver"
              class="w-full rounded border border-slate-300 bg-transparent px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            />
            <p v-if="form.errors.driver" class="mt-1 text-xs text-rose-600">{{ form.errors.driver }}</p>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700" for="customer_id">Customer</label>
            <select
              id="customer_id"
              v-model="form.customer_id"
              required
              class="w-full rounded border border-slate-300 bg-transparent px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            >
              <option value="" disabled>Pilih Customer</option>
              <option v-for="customer in customers" :key="customer.customers_id_odoo" :value="customer.customers_id_odoo">
                {{ customer.name }}
              </option>
            </select>
            <p v-if="form.errors.customer_id" class="mt-1 text-xs text-rose-600">{{ form.errors.customer_id }}</p>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700" for="transaksi">Transaksi</label>
            <select
              id="transaksi"
              v-model="form.transaksi"
              required
              class="w-full rounded border border-slate-300 bg-transparent px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            >
              <option value="" disabled>Pilih Transaksi</option>
              <option value="Inbound">Inbound</option>
              <option value="Outbound">Outbound</option>
            </select>
            <p v-if="form.errors.transaksi" class="mt-1 text-xs text-rose-600">{{ form.errors.transaksi }}</p>
          </div>

          <div class="flex items-center justify-end gap-2">
            <button
              type="button"
              class="rounded bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-300"
              @click="closeModal"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="form.processing"
              class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
            >
              {{ form.processing ? 'Menyimpan...' : 'Create' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Add Tally -->
    <div
      v-if="showTallyModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
    >
      <div class="max-h-[90vh] w-full max-w-2xl overflow-hidden rounded-xl border border-slate-300 bg-white p-5 shadow-2xl">
        <div class="mb-4 flex items-center justify-between gap-4">
          <h3 class="text-base font-semibold text-black">
            Add Tally
            <span v-if="selectedPo" class="ml-2 text-sm font-normal text-slate-400">
              (PO {{ selectedPo.po }} - {{ selectedPo.customer?.name || '-' }})
            </span>
          </h3>
          <button type="button" class="text-slate-400 hover:text-slate-600" @click="closeTallyModal">
            &times;
          </button>
        </div>

        <form @submit.prevent="addEntry" class="space-y-4">
          <div>
            <label class="mb-1 block bg-white text-sm font-medium text-slate-700" for="item">Item</label>
            <select
              id="item"
              v-model="tallyForm.item"
              required
              :disabled="itemLocked"
              class="w-full rounded border border-slate-300 bg-transparent px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400"
            >
              <option value="" disabled>Pilih Item</option>
              <option v-for="product in filteredProducts" :key="product.id" :value="product.internal_reference">
                {{ product.internal_reference }} - {{ product.name }}
              </option>
            </select>
          </div>

          <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:gap-4">
            <div class="flex-1">
              <label class="mb-1 block bg-white text-sm font-medium text-slate-700" for="pallet">Pallet</label>
              <input
                id="pallet"
                :value="currentPallet"
                type="number"
                min="1"
                readonly
                class="w-full rounded border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              />
            </div>
            <div class="flex-1">
              <label class="mb-1 block bg-white text-sm font-medium text-slate-700" for="kg">KG</label>
              <input
                id="kg"
                v-model="tallyForm.kg"
                type="number"
                min="0"
                step="0.01"
                required
                placeholder=""
                class="w-full rounded border border-slate-300 bg-transparent px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              />
            </div>
            <button
              type="submit"
              :disabled="!tallyForm.item"
              class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
              OK
            </button>
          </div>
        </form>

        <div class="mt-6">
          <h4 class="mb-2 text-sm font-semibold text-slate-700">List Inputan KG — Pallet {{ currentPallet }}</h4>
          <div v-if="currentEntries.length === 0" class="rounded-md border border-dashed border-slate-300 p-6 text-center text-sm text-slate-400">
            Belum ada inputan KG.
          </div>
          <div v-else class="max-h-64 overflow-y-auto rounded-md border border-slate-200">
            <table class="w-full border-collapse text-sm">
              <thead class="sticky top-0">
                <tr class="bg-slate-50 text-left text-slate-500">
                  <th class="whitespace-nowrap border-b border-slate-200 px-4 py-2 font-semibold">#</th>
                  <th class="whitespace-nowrap border-b border-slate-200 px-4 py-2 font-semibold">Item</th>
                  <th class="whitespace-nowrap border-b border-slate-200 px-4 py-2 font-semibold">Pallet</th>
                  <th class="whitespace-nowrap border-b border-slate-200 px-4 py-2 font-semibold">KG</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(entry, index) in currentEntries" :key="index" class="text-slate-800 hover:bg-slate-50">
                  <td class="whitespace-nowrap border-b border-slate-100 px-4 py-2">{{ index + 1 }}</td>
                  <td class="whitespace-nowrap border-b border-slate-100 px-4 py-2">{{ entry.item }}</td>
                  <td class="whitespace-nowrap border-b border-slate-100 px-4 py-2">{{ entry.pallet }}</td>
                  <td class="whitespace-nowrap border-b border-slate-100 px-4 py-2">{{ Number(entry.kg).toFixed(2) }}</td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="bg-slate-50 font-semibold text-slate-700">
                  <td colspan="3" class="border-t border-slate-200 px-4 py-2 text-right">Total KG</td>
                  <td class="whitespace-nowrap border-t border-slate-200 px-4 py-2">{{ totalKg }}</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <div class="mt-6 flex items-center justify-end gap-2">
          <button
            type="button"
            class="rounded bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-300"
            @click="closeTallyModal"
          >
            Close
          </button>
          <button
            type="button"
            :disabled="saving"
            class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
            @click="finishTally"
          >
            {{ saving ? 'Menyimpan...' : 'Finish' }}
          </button>
          <button
            type="button"
            :disabled="currentPallet <= 1"
            class="rounded bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700 disabled:cursor-not-allowed disabled:opacity-50"
            @click="prevPallet"
          >
            Prev
          </button>
          <button
            type="button"
            :disabled="isNextDisabled"
            class="rounded bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
            @click="nextPallet"
          >
            Next
          </button>
          <button
            type="button"
            :disabled="!hasUnsaved || saving"
            class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
            @click="saveEntries"
          >
            {{ saving ? 'Menyimpan...' : 'Save' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Summary -->
    <div
      v-if="showSummary && summaryData"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
    >
      <div class="w-full max-w-md overflow-hidden rounded-xl border border-slate-300 bg-white p-6 shadow-2xl">
        <div class="mb-4 flex items-center justify-between">
          <h3 class="text-lg font-semibold text-slate-900">Tally Selesai</h3>
          <button type="button" class="text-slate-400 hover:text-slate-600" @click="showSummary = false">
            &times;
          </button>
        </div>

        <div class="space-y-3 text-sm">
          <div class="flex justify-between border-b border-slate-100 pb-2">
            <span class="font-medium text-slate-500">PO</span>
            <span class="text-slate-900">{{ summaryData.po }}</span>
          </div>
          <div class="flex justify-between border-b border-slate-100 pb-2">
            <span class="font-medium text-slate-500">Customer</span>
            <span class="text-slate-900">{{ summaryData.customer }}</span>
          </div>
          <div class="flex justify-between border-b border-slate-100 pb-2">
            <span class="font-medium text-slate-500">Nopol</span>
            <span class="text-slate-900">{{ summaryData.nopol }}</span>
          </div>
          <div class="flex justify-between border-b border-slate-100 pb-2">
            <span class="font-medium text-slate-500">Driver</span>
            <span class="text-slate-900">{{ summaryData.driver }}</span>
          </div>
          <div class="flex justify-between border-b border-slate-100 pb-2">
            <span class="font-medium text-slate-500">Item</span>
            <span class="text-slate-900">{{ summaryData.item }}</span>
          </div>
          <div class="flex justify-between border-b border-slate-100 pb-2">
            <span class="font-medium text-slate-500">Qty Total</span>
            <span class="text-slate-900">{{ summaryData.totalPallets }} Pallet</span>
          </div>
          <div class="flex justify-between pb-2">
            <span class="font-medium text-slate-500">KG's</span>
            <span class="text-lg font-bold text-slate-900">{{ Number(summaryData.totalKg).toFixed(2) }} KG</span>
          </div>
        </div>

        <div class="mt-5 flex justify-end">
          <button
            type="button"
            class="rounded bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
            @click="showSummary = false"
          >
            OK
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  customers: {
    type: Array,
    default: () => [],
  },
  tallies: {
    type: Array,
    default: () => [],
  },
  products: {
    type: Array,
    default: () => [],
  },
  tallyMaxPallet: {
    type: Object,
    default: () => ({}),
  },
  finishedPoIds: {
    type: Array,
    default: () => [],
  },
  tallyData: {
    type: Object,
    default: () => ({}),
  },
  flash: {
    type: Object,
    default: () => ({}),
  },
});

const customers = computed(() => props.customers || []);
const tallies = computed(() => props.tallies || []);
const products = computed(() => props.products || []);
const finishedPoIds = ref([...(props.finishedPoIds || [])]);
const flashMessage = computed(() => props.flash?.success || '');

const showModal = ref(false);
const selectedId = ref(null);

function toggleSelect(id) {
  selectedId.value = selectedId.value === id ? null : id;
}

const selectedPo = computed(() => tallies.value.find((tally) => tally.id === selectedId.value) || null);

const filteredProducts = computed(() => {
  if (!selectedPo.value) {
    return [];
  }
  const customerId = selectedPo.value.customer_id;
  return products.value.filter((product) => String(product.customer_id) === String(customerId));
});

const showTallyModal = ref(false);
const showSummary = ref(false);
const saving = ref(false);
const summaryData = ref(null);
const itemLocked = ref(false);
const tallyForm = ref({
  item: '',
  kg: '',
});
const palletEntries = ref({});
const currentPallet = ref(1);
const tallyStates = ref({});
const hasUnsaved = ref(false);

const currentEntries = computed(() => palletEntries.value[currentPallet.value] || []);

const totalKg = computed(() => {
  const sum = currentEntries.value.reduce((acc, entry) => acc + Number(entry.kg), 0);
  return Number.isFinite(sum) ? sum : 0;
});

const summaryTotalPallets = computed(() => Object.keys(palletEntries.value).length);

const summaryTotalKg = computed(() => {
  let total = 0;
  for (const entries of Object.values(palletEntries.value)) {
    for (const entry of entries) {
      total += Number(entry.kg);
    }
  }
  return Number.isFinite(total) ? total : 0;
});

const isPoFinished = computed(() => {
  if (!selectedPo.value) {
    return false;
  }
  return finishedPoIds.value.includes(selectedPo.value.id);
});

const isNextDisabled = computed(() => {
  return currentEntries.value.some((e) => e._new);
});

function getUnsavedEntries() {
  const all = [];
  for (const entries of Object.values(palletEntries.value)) {
    for (const entry of entries) {
      if (entry._new) {
        all.push(entry);
      }
    }
  }
  return all;
}

function openTallyModal() {
  if (!selectedPo.value) {
    return;
  }
  const poId = selectedPo.value.id;
  const saved = tallyStates.value[poId];
  if (saved) {
    tallyForm.value = { item: saved.item || '', kg: '' };
    currentPallet.value = saved.currentPallet || 1;
    palletEntries.value = { ...saved.palletEntries };
    itemLocked.value = saved.itemLocked || false;
  } else {
    const maxPallet = props.tallyMaxPallet[poId] || 0;
    const existingData = props.tallyData[poId] || [];
    const restoredEntries = {};
    existingData.forEach((row) => {
      const p = row.pallet;
      if (!restoredEntries[p]) {
        restoredEntries[p] = [];
      }
      restoredEntries[p].push({
        item: row.item,
        pallet: row.pallet,
        kg: row.kg,
        _new: false,
      });
    });
    tallyForm.value = { item: existingData.length > 0 ? existingData[0].item : '', kg: '' };
    currentPallet.value = maxPallet + 1;
    palletEntries.value = { ...restoredEntries };
    itemLocked.value = maxPallet > 0;
  }
  hasUnsaved.value = false;
  showTallyModal.value = true;
}

function closeTallyModal() {
  if (saving.value) {
    return;
  }
  if (selectedPo.value) {
    tallyStates.value[selectedPo.value.id] = {
      item: tallyForm.value.item,
      currentPallet: currentPallet.value,
      palletEntries: { ...palletEntries.value },
      itemLocked: itemLocked.value,
    };
  }
  showTallyModal.value = false;
}

function deleteTally() {
  if (!selectedId.value) {
    return;
  }
  if (!window.confirm('Hapus data tally dari PO ini?')) {
    return;
  }
  router.delete(`/gmisl/utility/rcs/tally/${selectedId.value}`, {
    preserveScroll: true,
    onSuccess: () => {
      selectedId.value = null;
    },
  });
}

function deletePo() {
  if (!selectedId.value) {
    return;
  }
  if (!window.confirm('Hapus PO ini beserta semua data tally-nya?')) {
    return;
  }
  router.delete(`/gmisl/utility/rcs/${selectedId.value}`, {
    preserveScroll: true,
    onSuccess: () => {
      selectedId.value = null;
    },
  });
}

function addEntry() {
  const item = tallyForm.value.item;
  const kg = tallyForm.value.kg;
  if (!item || kg === '' || !selectedPo.value) {
    return;
  }
  const updated = { ...palletEntries.value };
  if (!updated[currentPallet.value]) {
    updated[currentPallet.value] = [];
  }
  updated[currentPallet.value] = [
    ...updated[currentPallet.value],
    { item: item, pallet: currentPallet.value, kg: kg, _new: true },
  ];
  palletEntries.value = updated;
  tallyForm.value.kg = '';
  hasUnsaved.value = true;
}

function nextPallet() {
  currentPallet.value += 1;
  tallyForm.value.kg = '';
}

function prevPallet() {
  if (currentPallet.value > 1) {
    currentPallet.value -= 1;
    tallyForm.value.kg = '';
  }
}

function saveEntries() {
  const unsaved = getUnsavedEntries();
  if (unsaved.length === 0 || !selectedPo.value || saving.value) {
    return;
  }
  saving.value = true;
  router.post(
    '/gmisl/utility/rcs/tally',
    {
      t_po_id: selectedPo.value.id,
      entries: unsaved.map((entry) => ({
        item: entry.item,
        pallet: entry.pallet,
        kg: entry.kg,
      })),
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        itemLocked.value = true;
        hasUnsaved.value = false;
        const updated = { ...palletEntries.value };
        for (const pallet of Object.keys(updated)) {
          updated[pallet] = updated[pallet].map((e) => ({ ...e, _new: false }));
        }
        palletEntries.value = updated;
      },
      onFinish: () => {
        saving.value = false;
      },
    }
  );
}

async function finishTally() {
  if (saving.value) {
    return;
  }
  const unsaved = getUnsavedEntries();
  const allEntries = [];
  for (const entries of Object.values(palletEntries.value)) {
    allEntries.push(...entries);
  }

  summaryData.value = {
    po: selectedPo.value?.po || '-',
    customer: selectedPo.value?.customer?.name || '-',
    nopol: selectedPo.value?.nopol || '-',
    driver: selectedPo.value?.driver || '-',
    item: tallyForm.value.item || '-',
    totalPallets: summaryTotalPallets.value,
    totalKg: summaryTotalKg.value,
  };

  if (unsaved.length > 0 && selectedPo.value) {
    saving.value = true;
    await new Promise((resolve) => {
      router.post(
        '/gmisl/utility/rcs/tally',
        {
          t_po_id: selectedPo.value.id,
          is_finish: true,
          entries: unsaved.map((entry) => ({
            item: entry.item,
            pallet: entry.pallet,
            kg: entry.kg,
          })),
        },
        {
          preserveScroll: true,
          onFinish: () => {
            saving.value = false;
            if (selectedPo.value && !finishedPoIds.value.includes(selectedPo.value.id)) {
              finishedPoIds.value.push(selectedPo.value.id);
            }
            resolve();
          },
        }
      );
    });
  }
  palletEntries.value = {};
  currentPallet.value = 1;
  if (selectedPo.value) {
    delete tallyStates.value[selectedPo.value.id];
  }
  showTallyModal.value = false;
  showSummary.value = true;
}
const form = useForm({
  po: '',
  nopol: '',
  driver: '',
  customer_id: '',
  transaksi: '',
});

function openModal() {
  form.reset();
  form.clearErrors();
  showModal.value = true;
}

function closeModal() {
  if (form.processing) {
    return;
  }
  showModal.value = false;
}

function submitForm() {
  form.post('/gmisl/utility/rcs', {
    preserveScroll: true,
    onSuccess: () => {
      closeModal();
    },
  });
}
</script>
