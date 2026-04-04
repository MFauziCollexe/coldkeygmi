<template>
  <AppLayout>
    <div class="space-y-6 p-4 md:p-6">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <h2 class="text-2xl font-bold">Stock Card</h2>
          <p class="text-sm text-slate-400">
            Kelola transaksi stock non-produk melalui tombol <span class="font-semibold text-slate-200">Add Stock</span> dan
            <span class="font-semibold text-slate-200">Request</span>, lalu pantau histori kartu stock sesuai hak akses.
          </p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
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

      <div
        v-if="pendingRequests.length"
        class="rounded border border-slate-700 bg-slate-800 p-4"
      >
        <div class="mb-4 flex items-center justify-between">
          <div>
            <div class="text-lg font-semibold text-slate-100">Pending Request</div>
            <div class="text-xs text-slate-400">Permintaan stock belum mengurangi stock sampai disetujui.</div>
          </div>
        </div>

        <div class="hidden overflow-x-auto lg:block">
          <table class="w-full table-auto text-sm">
            <thead>
              <tr class="text-left text-slate-400">
                <th class="py-2">Tanggal</th>
                <th>Barang</th>
                <th>Qty</th>
                <th>Peminta</th>
                <th>Keterangan</th>
                <th v-if="abilities.approve_request" class="text-right">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="requestItem in pendingRequests"
                :key="requestItem.id"
                class="border-t border-slate-700"
              >
                <td class="py-3">{{ requestItem.request_date }}</td>
                <td>{{ requestItem.item_name }}</td>
                <td>{{ requestItem.quantity }}</td>
                <td>{{ requestItem.requested_by_name }}</td>
                <td>{{ requestItem.notes || requestItem.creator_name || '-' }}</td>
                <td v-if="abilities.approve_request" class="text-right">
                  <button
                    type="button"
                    class="rounded bg-indigo-600 px-3 py-1 text-xs font-semibold text-white transition hover:bg-indigo-500"
                    @click="approveRequest(requestItem.id)"
                  >
                    Approve
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="space-y-3 lg:hidden">
          <div
            v-for="requestItem in pendingRequests"
            :key="requestItem.id"
            class="rounded-xl border border-slate-700 bg-slate-900/40 p-4"
          >
            <div class="mb-3 flex items-start justify-between gap-3">
              <div>
                <div class="font-semibold text-slate-100">{{ requestItem.item_name }}</div>
                <div class="text-sm text-slate-400">{{ requestItem.request_date }}</div>
              </div>
              <div class="rounded bg-amber-950/50 px-2 py-1 text-xs font-semibold text-amber-300">
                Qty {{ requestItem.quantity }}
              </div>
            </div>

            <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
              <div>
                <div class="text-slate-400">Peminta</div>
                <div>{{ requestItem.requested_by_name }}</div>
              </div>
              <div>
                <div class="text-slate-400">Keterangan</div>
                <div>{{ requestItem.notes || requestItem.creator_name || '-' }}</div>
              </div>
            </div>

            <div v-if="abilities.approve_request" class="mt-4">
              <button
                type="button"
                class="w-full rounded bg-indigo-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500"
                @click="approveRequest(requestItem.id)"
              >
                Approve
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1fr)_320px]">
        <div class="rounded border border-slate-700 bg-slate-800 p-4">
          <input
            v-model="filters.q"
            type="text"
            placeholder="Cari nama barang / tipe / code..."
            class="w-full rounded border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-100"
            @input="onSearchInput"
          />
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

      <div
        v-if="!abilities.view_history"
        class="rounded border border-slate-700 bg-slate-800 p-6 text-sm text-slate-300"
      >
        Anda tidak memiliki akses untuk melihat histori kartu stock. Jika perlu, ability
        <span class="font-semibold text-slate-100">view_history</span> bisa diatur lewat menu Access Rules.
      </div>

      <div v-else class="rounded border border-slate-300 bg-white p-4 text-black shadow-sm">
        <div class="overflow-x-auto border border-black">
          <table class="min-w-full border-collapse text-sm">
            <tbody>
              <tr>
                <td rowspan="2" class="w-32 border border-black px-2 py-3 text-center md:w-40 md:px-3">
                  <img
                    src="/image/logo-gmi-clean.png"
                    alt="PT. Golden Multi Indotama"
                    class="mx-auto h-12 w-12 object-contain md:h-16 md:w-16"
                  />
                </td>
                <td class="border border-black px-3 py-2 text-center text-lg font-bold md:text-2xl">FORMULIR</td>
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
                <td class="border border-black px-3 py-4 text-center text-base font-semibold md:text-xl">KARTU STOCK BARANG - NON PRODUK</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-4 overflow-x-auto border border-black">
          <table class="min-w-full border-collapse text-sm">
            <tbody>
              <tr>
                <td class="border border-black px-3 py-2 font-semibold">Nama Barang</td>
                <td class="border border-black px-3 py-2">
                  :
                  <select
                    v-model="filters.item_id"
                    class="ml-2 min-w-[180px] border-none bg-transparent px-1 py-0 text-sm text-black focus:outline-none md:min-w-[240px]"
                    @change="fetchList"
                  >
                    <option value="all">All</option>
                    <option
                      v-for="item in items"
                      :key="item.id"
                      :value="String(item.id)"
                    >
                      {{ item.name }}
                    </option>
                  </select>
                </td>
              </tr>
              <tr>
                <td class="border border-black px-3 py-2 font-semibold">Jenis / Tipe Barang</td>
                <td class="border border-black px-3 py-2">
                  :
                  <template v-if="selectedItem">
                    {{ selectedItem.item_type }} <span class="ml-8 font-semibold">Satuan:</span> {{ selectedItem.unit }}
                  </template>
                  <template v-else>
                    Semua Barang <span class="ml-8 font-semibold">Satuan:</span> -
                  </template>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-4 border border-black lg:hidden">
          <div
            v-if="!cardRows.length"
            class="px-3 py-6 text-center text-sm text-slate-500"
          >
            <span v-if="selectedItem">Belum ada histori penambahan atau permintaan untuk barang ini.</span>
            <span v-else>Belum ada histori penambahan atau permintaan untuk semua barang.</span>
          </div>
          <div v-else class="space-y-0">
            <div
              v-for="(row, index) in cardRows"
              :key="`mobile-row-${index}`"
              class="border-b border-black px-3 py-3 last:border-b-0"
              :class="row.is_latest_balance ? 'bg-sky-100' : ''"
            >
              <div class="grid grid-cols-1 gap-2 text-sm">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <div class="font-semibold">{{ row.item_name }}</div>
                    <div class="text-xs text-slate-600">{{ row.date }}</div>
                  </div>
                  <div class="text-right">
                    <div class="text-xs text-slate-600">Sisa Stock</div>
                    <div class="font-semibold">{{ row.balance }}</div>
                  </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                  <div>
                    <div class="text-xs text-slate-600">Masuk</div>
                    <div>{{ row.incoming }}</div>
                  </div>
                  <div>
                    <div class="text-xs text-slate-600">Dipakai</div>
                    <div>{{ row.outgoing }}</div>
                  </div>
                </div>
                <div>
                  <div class="text-xs text-slate-600">Keterangan</div>
                  <div>{{ row.note }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-4 hidden overflow-x-auto border border-black lg:block">
          <table class="min-w-full border-collapse text-sm">
            <thead>
              <tr class="bg-slate-100">
                <th class="border border-black px-3 py-2 text-center">Tanggal</th>
                <th class="border border-black px-3 py-2 text-center">Nama Barang</th>
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
                :class="row.is_latest_balance ? 'bg-sky-100' : ''"
              >
                <td class="border border-black px-3 py-2">{{ row.date }}</td>
                <td class="border border-black px-3 py-2">{{ row.item_name }}</td>
                <td class="border border-black px-3 py-2 text-center">{{ row.incoming }}</td>
                <td class="border border-black px-3 py-2 text-center">{{ row.outgoing }}</td>
                <td class="border border-black px-3 py-2 text-center font-semibold">
                  {{ row.balance }}
                </td>
                <td class="border border-black px-3 py-2">{{ row.note }}</td>
              </tr>
              <tr v-if="!cardRows.length">
                <td colspan="6" class="border border-black px-3 py-6 text-center text-slate-500">
                  <span v-if="selectedItem">Belum ada histori penambahan atau permintaan untuk barang ini.</span>
                  <span v-else>Belum ada histori penambahan atau permintaan untuk semua barang.</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div
      v-if="showStockInModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4"
    >
      <div class="w-full max-w-lg rounded border border-slate-700 bg-slate-900 p-4 shadow-xl md:p-6">
        <div class="mb-4 flex items-start justify-between gap-3">
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
          <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <input
              v-model="stockInForm.transaction_date"
              type="date"
              class="w-full rounded border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-100"
            />
            <input
              v-model="stockInForm.quantity"
              type="number"
              min="1"
              step="1"
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

          <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
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
      <div class="w-full max-w-lg rounded border border-slate-700 bg-slate-900 p-4 shadow-xl md:p-6">
        <div class="mb-4 flex items-start justify-between gap-3">
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
          <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <input
              v-model="requestForm.request_date"
              type="date"
              class="w-full rounded border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-100"
            />
            <input
              v-model="requestForm.quantity"
              type="number"
              min="1"
              step="1"
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

          <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
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
      item_id: 'all',
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
      approve_request: false,
      view_history: false,
    }),
  },
  pendingRequests: {
    type: Array,
    default: () => [],
  },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success || '');
const items = computed(() => props.items || []);
const selectedItem = computed(() => props.selectedItem || null);
const cardRows = computed(() => props.cardRows || []);
const meta = computed(() => props.meta || {});
const abilities = computed(() => props.abilities || {});
const pendingRequests = computed(() => props.pendingRequests || []);
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
  item_id: props.filters?.item_id || 'all',
});

const stockInForm = useForm({
  stock_card_item_id: props.selectedItem?.id ? String(props.selectedItem.id) : '',
  transaction_date: today,
  quantity: '',
  notes: '',
  return_item_id: props.filters?.item_id || 'all',
  return_q: props.filters?.q || '',
});

const requestForm = useForm({
  stock_card_item_id: props.selectedItem?.id ? String(props.selectedItem.id) : '',
  request_date: today,
  quantity: '',
  requested_by_name: currentUserName.value,
  notes: '',
  return_item_id: props.filters?.item_id || 'all',
  return_q: props.filters?.q || '',
});

let searchTimer = null;

function currentQuery() {
  const query = {};
  if (filters.q) query.q = filters.q;
  if (filters.item_id && filters.item_id !== 'all') query.item_id = filters.item_id;
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

function preferredItemId() {
  if (filters.item_id && filters.item_id !== 'all') {
    return filters.item_id;
  }

  return items.value[0] ? String(items.value[0].id) : '';
}

function openStockInModal() {
  stockInForm.stock_card_item_id = preferredItemId();
  stockInForm.return_item_id = filters.item_id || 'all';
  stockInForm.return_q = filters.q || '';
  showStockInModal.value = true;
}

function closeStockInModal() {
  showStockInModal.value = false;
}

function openRequestModal() {
  requestForm.stock_card_item_id = preferredItemId();
  requestForm.requested_by_name = requestForm.requested_by_name || currentUserName.value;
  requestForm.return_item_id = filters.item_id || 'all';
  requestForm.return_q = filters.q || '';
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

function approveRequest(requestId) {
  router.post(`/gmisl/utility/stock-card/requests/${requestId}/approve`, {
    return_item_id: filters.item_id || 'all',
    return_q: filters.q || '',
  }, {
    preserveScroll: true,
  });
}
</script>
