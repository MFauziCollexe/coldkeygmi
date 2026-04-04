<template>
  <AppLayout>
    <div class="p-6 space-y-6">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <h2 class="text-2xl font-bold">Stock Card</h2>
          <p class="text-sm text-slate-400">
            Kelola transaksi stock non-produk melalui tombol <span class="font-semibold text-slate-200">Add Stock</span> dan
            <span class="font-semibold text-slate-200">Request</span>, lalu pantau histori kartu stock sesuai hak akses.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <button
            v-if="abilities.add_stock"
            type="button"
            class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="!items.length"
            @click="openStockInModal"
          >
            Add Stock
          </button>
          <button
            v-if="abilities.request_stock"
            type="button"
            class="rounded bg-amber-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-500 disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="!items.length"
            @click="openRequestModal"
          >
            Request
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

      <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1fr)_320px]">
        <div class="rounded border border-slate-700 bg-slate-800 p-4">
          <div class="grid grid-cols-1 gap-3 md:grid-cols-[minmax(0,1fr)_220px]">
            <input
              v-model="filters.q"
              type="text"
              placeholder="Cari nama barang / tipe / code..."
              class="rounded border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-100"
              @input="onSearchInput"
            />
            <select
              v-model="filters.item_id"
              class="rounded border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-100"
              @change="fetchList"
            >
              <option value="">Pilih Barang</option>
              <option
                v-for="item in items"
                :key="item.id"
                :value="String(item.id)"
              >
                {{ item.name }}
              </option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="rounded border border-slate-700 bg-slate-800 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-400">Total Item</div>
            <div class="mt-2 text-2xl font-bold text-slate-100">{{ meta.total_items || 0 }}</div>
          </div>
          <div class="rounded border border-slate-700 bg-slate-800 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-400">Low Stock</div>
            <div class="mt-2 text-2xl font-bold text-rose-400">{{ meta.low_stock_items || 0 }}</div>
          </div>
        </div>
      </div>

      <div v-if="selectedItem" class="rounded border border-slate-700 bg-slate-800 p-4">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
          <div>
            <div class="text-lg font-semibold text-slate-100">{{ selectedItem.name }}</div>
            <div class="text-sm text-slate-400">
              {{ selectedItem.item_type }} | {{ selectedItem.unit }} | Code: {{ selectedItem.item_code }}
            </div>
          </div>
          <div class="flex flex-wrap items-center gap-2 text-sm">
            <span class="inline-flex rounded bg-slate-700 px-3 py-1 font-semibold text-slate-100">
              Stock: {{ selectedItem.current_stock }} {{ selectedItem.unit }}
            </span>
            <span class="inline-flex rounded bg-slate-700 px-3 py-1 font-semibold text-slate-100">
              Min: {{ selectedItem.minimum_stock }} {{ selectedItem.unit }}
            </span>
          </div>
        </div>
      </div>

      <div
        v-if="!abilities.view_history"
        class="rounded border border-slate-700 bg-slate-800 p-6 text-sm text-slate-300"
      >
        Anda tidak memiliki akses untuk melihat histori kartu stock. Jika perlu, ability
        <span class="font-semibold text-slate-100">view_history</span> bisa diatur lewat menu Access Rules.
      </div>

      <div v-else class="grid grid-cols-1 gap-6 xl:grid-cols-[340px_minmax(0,1fr)]">
        <div class="rounded border border-slate-700 bg-slate-800 p-4">
          <div class="mb-4">
            <div class="text-lg font-semibold text-slate-100">Daftar Barang</div>
            <div class="text-xs text-slate-400">Pilih barang untuk melihat histori penambahan dan permintaan stock.</div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full table-auto text-sm">
              <thead>
                <tr class="text-left text-slate-400">
                  <th class="py-2">Barang</th>
                  <th class="py-2">Stock</th>
                  <th class="py-2"></th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="item in items"
                  :key="item.id"
                  class="border-t border-slate-700"
                >
                  <td class="py-3">
                    <div class="font-medium text-slate-100">{{ item.name }}</div>
                    <div class="text-xs text-slate-400">{{ item.item_type }} | {{ item.unit }}</div>
                  </td>
                  <td class="py-3">
                    <span
                      class="inline-flex rounded px-2 py-1 text-xs font-semibold"
                      :class="Number(item.current_stock) <= Number(item.minimum_stock)
                        ? 'bg-rose-600 text-white'
                        : 'bg-emerald-600 text-white'"
                    >
                      {{ item.current_stock }}
                    </span>
                  </td>
                  <td class="py-3 text-right">
                    <button
                      type="button"
                      class="text-indigo-400 hover:text-indigo-300"
                      @click="selectItem(item.id)"
                    >
                      View
                    </button>
                  </td>
                </tr>
                <tr v-if="!items.length">
                  <td colspan="3" class="py-6 text-center text-slate-500">Belum ada master barang aktif.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="rounded border border-slate-300 bg-white p-4 text-black shadow-sm">
          <div class="overflow-x-auto border border-black">
            <table class="min-w-full border-collapse text-sm">
              <tbody>
                <tr>
                  <td rowspan="2" class="w-40 border border-black px-3 py-3 text-center">
                    <img
                      src="/image/logo-gmi-clean.png"
                      alt="PT. Golden Multi Indotama"
                      class="mx-auto h-16 w-16 object-contain"
                    />
                  </td>
                  <td class="border border-black px-3 py-2 text-center text-2xl font-bold">FORMULIR</td>
                  <td class="border border-black p-0 align-top" rowspan="2">
                    <table class="min-w-full border-collapse text-sm">
                      <tbody>
                        <tr><td class="border border-black px-2 py-1">No Dokumen</td><td class="border border-black px-2 py-1">{{ meta.document_no }}</td></tr>
                        <tr><td class="border border-black px-2 py-1">Revision No.</td><td class="border border-black px-2 py-1">{{ meta.revision_no }}</td></tr>
                        <tr><td class="border border-black px-2 py-1">Effective date</td><td class="border border-black px-2 py-1">{{ meta.effective_date }}</td></tr>
                        <tr><td class="border border-black px-2 py-1">Page</td><td class="border border-black px-2 py-1">{{ meta.page }}</td></tr>
                        <tr><td class="border border-black px-2 py-1">Classification</td><td class="border border-black px-2 py-1">{{ meta.classification }}</td></tr>
                      </tbody>
                    </table>
                  </td>
                </tr>
                <tr>
                  <td class="border border-black px-3 py-4 text-center text-xl font-semibold">KARTU STOCK BARANG - NON PRODUK</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="selectedItem" class="mt-4 overflow-x-auto border border-black">
            <table class="min-w-full border-collapse text-sm">
              <tbody>
                <tr>
                  <td class="border border-black px-3 py-2 font-semibold">Nama Barang</td>
                  <td class="border border-black px-3 py-2">: {{ selectedItem.name }}</td>
                </tr>
                <tr>
                  <td class="border border-black px-3 py-2 font-semibold">Jenis / Tipe Barang</td>
                  <td class="border border-black px-3 py-2">: {{ selectedItem.item_type }} <span class="ml-8 font-semibold">Satuan:</span> {{ selectedItem.unit }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="selectedItem" class="mt-4 overflow-x-auto border border-black">
            <table class="min-w-full border-collapse text-sm">
              <thead>
                <tr class="bg-slate-100">
                  <th class="border border-black px-3 py-2 text-center">Tanggal</th>
                  <th class="border border-black px-3 py-2 text-center">Masuk</th>
                  <th class="border border-black px-3 py-2 text-center">Dipakai</th>
                  <th class="border border-black px-3 py-2 text-center">Sisa Stock</th>
                  <th class="border border-black px-3 py-2 text-center">Keterangan</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(row, index) in cardRows"
                  :key="`row-${index}`"
                >
                  <td class="border border-black px-3 py-2">{{ row.date }}</td>
                  <td class="border border-black px-3 py-2 text-center">{{ row.incoming }}</td>
                  <td class="border border-black px-3 py-2 text-center">{{ row.outgoing }}</td>
                  <td class="border border-black px-3 py-2 text-center">{{ row.balance }}</td>
                  <td class="border border-black px-3 py-2">{{ row.note }}</td>
                </tr>
                <tr v-if="!cardRows.length">
                  <td colspan="5" class="border border-black px-3 py-6 text-center text-slate-500">
                    Belum ada histori penambahan atau permintaan untuk barang ini.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-else class="mt-6 rounded border border-dashed border-slate-300 p-8 text-center text-slate-500">
            Pilih salah satu barang untuk melihat histori kartu stock.
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="showStockInModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4"
    >
      <div class="w-full max-w-lg rounded border border-slate-700 bg-slate-900 p-6 shadow-xl">
        <div class="mb-4 flex items-center justify-between">
          <div>
            <div class="text-lg font-semibold text-slate-100">Add Stock</div>
            <div class="text-sm text-slate-400">Tambahkan stock barang ke kartu stock.</div>
          </div>
          <button type="button" class="text-slate-400 hover:text-slate-200" @click="closeStockInModal">Tutup</button>
        </div>

        <form class="space-y-4" @submit.prevent="submitStockIn">
          <select
            v-model="stockInForm.stock_card_item_id"
            class="w-full rounded border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-100"
          >
            <option value="">Pilih Barang</option>
            <option
              v-for="item in items"
              :key="`in-${item.id}`"
              :value="String(item.id)"
            >
              {{ item.name }}
            </option>
          </select>
          <div class="grid grid-cols-2 gap-3">
            <input
              v-model="stockInForm.transaction_date"
              type="date"
              class="w-full rounded border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-100"
            />
            <input
              v-model="stockInForm.quantity"
              type="number"
              min="0.01"
              step="0.01"
              placeholder="Qty Masuk"
              class="w-full rounded border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-100"
            />
          </div>
          <textarea
            v-model="stockInForm.notes"
            rows="3"
            placeholder="Keterangan"
            class="w-full rounded border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-100"
          ></textarea>

          <div class="flex justify-end gap-2">
            <button
              type="button"
              class="rounded border border-slate-700 px-4 py-2 text-sm text-slate-200"
              @click="closeStockInModal"
            >
              Batal
            </button>
            <button
              type="submit"
              class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-500"
              :disabled="stockInForm.processing"
            >
              Simpan
            </button>
          </div>
        </form>
      </div>
    </div>

    <div
      v-if="showRequestModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4"
    >
      <div class="w-full max-w-lg rounded border border-slate-700 bg-slate-900 p-6 shadow-xl">
        <div class="mb-4 flex items-center justify-between">
          <div>
            <div class="text-lg font-semibold text-slate-100">Request Stock</div>
            <div class="text-sm text-slate-400">Catat pengeluaran stock dari barang terpilih.</div>
          </div>
          <button type="button" class="text-slate-400 hover:text-slate-200" @click="closeRequestModal">Tutup</button>
        </div>

        <form class="space-y-4" @submit.prevent="submitRequest">
          <select
            v-model="requestForm.stock_card_item_id"
            class="w-full rounded border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-100"
          >
            <option value="">Pilih Barang</option>
            <option
              v-for="item in items"
              :key="`req-${item.id}`"
              :value="String(item.id)"
            >
              {{ item.name }}
            </option>
          </select>
          <div class="grid grid-cols-2 gap-3">
            <input
              v-model="requestForm.request_date"
              type="date"
              class="w-full rounded border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-100"
            />
            <input
              v-model="requestForm.quantity"
              type="number"
              min="0.01"
              step="0.01"
              placeholder="Qty Dipakai"
              class="w-full rounded border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-100"
            />
          </div>
          <input
            v-model="requestForm.requested_by_name"
            type="text"
            placeholder="Nama Peminta"
            class="w-full rounded border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-100"
          />
          <textarea
            v-model="requestForm.notes"
            rows="3"
            placeholder="Keterangan"
            class="w-full rounded border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-100"
          ></textarea>

          <div class="flex justify-end gap-2">
            <button
              type="button"
              class="rounded border border-slate-700 px-4 py-2 text-sm text-slate-200"
              @click="closeRequestModal"
            >
              Batal
            </button>
            <button
              type="submit"
              class="rounded bg-amber-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-500"
              :disabled="requestForm.processing"
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
  selectedItem: {
    type: Object,
    default: null,
  },
  cardRows: {
    type: Array,
    default: () => [],
  },
  filters: {
    type: Object,
    default: () => ({
      q: '',
      item_id: '',
    }),
  },
  meta: {
    type: Object,
    default: () => ({}),
  },
  abilities: {
    type: Object,
    default: () => ({
      add_stock: false,
      request_stock: false,
      view_history: false,
    }),
  },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success || '');
const items = computed(() => props.items || []);
const selectedItem = computed(() => props.selectedItem || null);
const cardRows = computed(() => props.cardRows || []);
const meta = computed(() => props.meta || {});
const abilities = computed(() => props.abilities || {});
const errorSummary = computed(() => {
  const errors = page.props.errors || {};
  return Object.values(errors).flat().filter(Boolean);
});

const today = new Date().toISOString().slice(0, 10);
const currentUserName = computed(() => page.props.auth?.user?.name || '');
const showStockInModal = ref(false);
const showRequestModal = ref(false);

const filters = reactive({
  q: props.filters?.q || '',
  item_id: props.filters?.item_id ? String(props.filters.item_id) : '',
});

const stockInForm = useForm({
  stock_card_item_id: props.filters?.item_id ? String(props.filters.item_id) : '',
  transaction_date: today,
  quantity: '',
  notes: '',
});

const requestForm = useForm({
  stock_card_item_id: props.filters?.item_id ? String(props.filters.item_id) : '',
  request_date: today,
  quantity: '',
  requested_by_name: currentUserName.value,
  notes: '',
});

let searchTimer = null;

function currentQuery() {
  const query = {};
  if (filters.q) query.q = filters.q;
  if (filters.item_id) query.item_id = filters.item_id;
  return query;
}

function fetchList() {
  router.get('/gmisl/utility/stock-card', currentQuery(), {
    preserveState: true,
    preserveScroll: true,
  });
}

function onSearchInput() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => fetchList(), 300);
}

function selectItem(itemId) {
  filters.item_id = String(itemId);
  stockInForm.stock_card_item_id = String(itemId);
  requestForm.stock_card_item_id = String(itemId);
  fetchList();
}

function preferredItemId() {
  return filters.item_id || (items.value[0] ? String(items.value[0].id) : '');
}

function openStockInModal() {
  stockInForm.stock_card_item_id = preferredItemId();
  showStockInModal.value = true;
}

function closeStockInModal() {
  showStockInModal.value = false;
}

function openRequestModal() {
  requestForm.stock_card_item_id = preferredItemId();
  requestForm.requested_by_name = requestForm.requested_by_name || currentUserName.value;
  showRequestModal.value = true;
}

function closeRequestModal() {
  showRequestModal.value = false;
}

function submitStockIn() {
  stockInForm.post('/gmisl/utility/stock-card/stock-in', {
    preserveScroll: true,
    onSuccess: () => {
      stockInForm.quantity = '';
      stockInForm.notes = '';
      closeStockInModal();
    },
  });
}

function submitRequest() {
  requestForm.post('/gmisl/utility/stock-card/requests', {
    preserveScroll: true,
    onSuccess: () => {
      requestForm.quantity = '';
      requestForm.notes = '';
      closeRequestModal();
    },
  });
}
</script>
