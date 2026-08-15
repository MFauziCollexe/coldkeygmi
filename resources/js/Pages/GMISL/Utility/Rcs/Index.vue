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
            @click="openTallyModal"
            :disabled="!selectedId"
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
            <label class="mb-1 block text-sm font-medium text-slate-700" for="item">Item</label>
            <select
              id="item"
              v-model="tallyForm.item"
              required
              class="w-full rounded border border-slate-300 bg-transparent px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            >
              <option value="" disabled>Pilih Item</option>
              <option v-for="product in filteredProducts" :key="product.id" :value="product.internal_reference">
                {{ product.internal_reference }} - {{ product.name }}
              </option>
            </select>
          </div>

          <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:gap-4">
            <div class="flex-1">
              <label class="mb-1 block text-sm font-medium text-slate-700" for="pallet">Pallet</label>
              <input
                id="pallet"
                v-model.number="tallyForm.pallet"
                type="number"
                min="1"
                readonly
                class="w-full rounded border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              />
            </div>
            <button
              type="button"
              @click="nextPallet"
              class="rounded bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
            >
              Next
            </button>
            <div class="flex-1">
              <label class="mb-1 block text-sm font-medium text-slate-700" for="kg">KG</label>
              <input
                id="kg"
                v-model="tallyForm.kg"
                type="number"
                min="0"
                step="any"
                required
                placeholder="Isi berat KG"
                class="w-full rounded border border-slate-300 bg-transparent px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              />
            </div>
            <button
              type="submit"
              class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
            >
              OK
            </button>
          </div>
        </form>

        <div class="mt-6">
          <h4 class="mb-2 text-sm font-semibold text-slate-700">List Inputan KG</h4>
          <div v-if="kgEntries.length === 0" class="rounded-md border border-dashed border-slate-300 p-6 text-center text-sm text-slate-400">
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
                <tr v-for="(entry, index) in kgEntries" :key="index" class="text-slate-800 hover:bg-slate-50">
                  <td class="whitespace-nowrap border-b border-slate-100 px-4 py-2">{{ index + 1 }}</td>
                  <td class="whitespace-nowrap border-b border-slate-100 px-4 py-2">{{ entry.item }}</td>
                  <td class="whitespace-nowrap border-b border-slate-100 px-4 py-2">{{ entry.pallet }}</td>
                  <td class="whitespace-nowrap border-b border-slate-100 px-4 py-2">{{ entry.kg }}</td>
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
            Cancel
          </button>
          <button
            type="button"
            :disabled="kgEntries.length === 0 || saving"
            class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
            @click="saveEntries"
          >
            {{ saving ? 'Menyimpan...' : 'Save' }}
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
  flash: {
    type: Object,
    default: () => ({}),
  },
});

const customers = computed(() => props.customers || []);
const tallies = computed(() => props.tallies || []);
const products = computed(() => props.products || []);
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
const saving = ref(false);
const tallyForm = ref({
  item: '',
  pallet: 1,
  kg: '',
});
const kgEntries = ref([]);

const totalKg = computed(() => {
  const sum = kgEntries.value.reduce((acc, entry) => acc + Number(entry.kg), 0);
  return Number.isFinite(sum) ? sum : 0;
});

function openTallyModal() {
  if (!selectedPo.value) {
    return;
  }
  tallyForm.value = { item: '', pallet: 1, kg: '' };
  showTallyModal.value = true;
}

function closeTallyModal() {
  if (saving.value) {
    return;
  }
  kgEntries.value = [];
  tallyForm.value = { item: '', pallet: 1, kg: '' };
  showTallyModal.value = false;
}

function nextPallet() {
  tallyForm.value.pallet += 1;
}

function addEntry() {
  const item = tallyForm.value.item;
  const kg = tallyForm.value.kg;
  if (!item || kg === '' || !selectedPo.value) {
    return;
  }
  kgEntries.value.push({
    item: item,
    pallet: tallyForm.value.pallet,
    kg: kg,
  });
  tallyForm.value.kg = '';
}

function saveEntries() {
  if (kgEntries.value.length === 0 || !selectedPo.value || saving.value) {
    return;
  }
  saving.value = true;
  router.post(
    '/gmisl/utility/rcs/tally',
    {
      t_po_id: selectedPo.value.id,
      entries: kgEntries.value.map((entry) => ({
        item: entry.item,
        pallet: entry.pallet,
        kg: entry.kg,
      })),
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        kgEntries.value = [];
        showTallyModal.value = false;
      },
      onFinish: () => {
        saving.value = false;
      },
    }
  );
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
