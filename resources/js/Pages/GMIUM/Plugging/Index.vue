<template>
  <AppLayout>
    <div class="w-full">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold">Plugging</h2>
        <div class="flex items-center gap-2">
          <Link href="/gmium/plugging/create" class="bg-indigo-600 px-4 py-2 rounded text-white">New Plugging</Link>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <div class="bg-slate-800 rounded p-4">
          <div class="text-sm text-slate-400">Total Plugging Hari Ini</div>
          <div class="text-2xl font-bold mt-1">{{ report.total_per_hari }}</div>
        </div>
        <div class="bg-slate-800 rounded p-4">
          <div class="text-sm text-slate-400">Total Plugging Bulan Ini</div>
          <div class="text-2xl font-bold mt-1">{{ report.total_per_bulan }}</div>
        </div>
        <div class="bg-slate-800 rounded p-4">
          <div class="text-sm text-slate-400">Rata-rata Durasi</div>
          <div class="text-2xl font-bold mt-1">{{ report.rata_durasi_menit }} menit</div>
        </div>
      </div>

      <div class="bg-slate-800 border border-slate-700 rounded-lg p-3 mb-4">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
          <div class="md:col-span-3">
            <div class="relative" @focusin="isTanggalFocused = true" @focusout="isTanggalFocused = false">
              <EnhancedDatePicker
                v-model="filters.tanggal"
                placeholder=" "
                input-class="w-full !h-[52px] px-3 !pt-5 !pb-2 rounded-lg !bg-slate-800 !border-slate-700 text-slate-100 placeholder-transparent"
              />
              <label
                :class="[
                  'pointer-events-none absolute left-3 z-10 transition-all',
                  ((isTanggalFocused || filters.tanggal)
                    ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                    : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
                ]"
              >
                Tanggal
              </label>
            </div>
          </div>
          <div class="md:col-span-3">
            <div class="relative" @focusin="isCustomerFocused = true" @focusout="isCustomerFocused = false">
              <SearchableSelect
                v-model="filters.customer"
                :options="customers"
                option-value="name"
                option-label="name"
                placeholder=" "
                empty-label="Pilih Customer"
                input-class="w-full h-[52px] pl-3 pr-10 pt-5 pb-2 !bg-slate-800 !border-slate-700 rounded-lg text-slate-100 placeholder-transparent"
                button-class="h-[52px] border-0 border-l !border-slate-700 rounded-r-lg !bg-slate-800 text-slate-100"
              />
              <label
                :class="[
                  'pointer-events-none absolute left-3 z-10 transition-all',
                  ((isCustomerFocused || filters.customer)
                    ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                    : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
                ]"
              >
                Customer
              </label>
            </div>
          </div>
          <div class="md:col-span-6 flex items-end gap-2">
            <button @click="fetch()" class="h-[52px] px-6 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-medium inline-flex items-center">Filter</button>
            <button @click="resetFilter" class="h-[52px] px-6 rounded-lg bg-slate-600 hover:bg-slate-500 text-white font-medium inline-flex items-center">Reset</button>
            <a :href="exportUrl" class="h-[52px] px-6 rounded-lg bg-green-600 hover:bg-green-500 text-white font-medium inline-flex items-center">Export Excel</a>
          </div>
        </div>
      </div>

      <div class="bg-slate-800 rounded p-4 overflow-x-auto">
        <table class="w-full table-auto min-w-[1200px]">
          <thead>
            <tr class="text-left text-slate-400 text-sm">
              <th class="py-2">Tanggal</th>
              <th>Jam Mulai</th>
              <th>Jam Selesai</th>
              <th>Durasi</th>
              <th>Customer</th>
              <th>No Container / No Polisi</th>
              <th>Suhu Awal</th>
              <th>Suhu Akhir</th>
              <th>Lokasi</th>
              <th>Status</th>
              <th>User</th>
              <th>Signature</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!pluggings.data || pluggings.data.length === 0" class="border-t border-slate-700">
              <td colspan="13" class="py-8 text-center text-slate-400">No data found.</td>
            </tr>
            <tr v-for="item in pluggings.data" :key="item.id" class="border-t border-slate-700 text-sm">
              <td class="py-3">{{ formatDate(item.tanggal) }}</td>
              <td>{{ item.jam_mulai || '-' }}</td>
              <td>{{ item.jam_selesai || '-' }}</td>
              <td>{{ item.durasi_menit ? `${item.durasi_menit} menit` : '-' }}</td>
              <td>{{ item.customer }}</td>
              <td>{{ item.no_container_no_polisi }}</td>
              <td>{{ item.suhu_awal }}</td>
              <td>{{ item.suhu_akhir ?? '-' }}</td>
              <td>{{ item.lokasi }}</td>
              <td>
                <span :class="item.status === 'selesai' ? 'bg-green-600' : 'bg-yellow-600'" class="px-2 py-1 rounded text-white text-xs">
                  {{ item.status }}
                </span>
              </td>
              <td>{{ item.user?.name || '-' }}</td>
              <td>
                <button
                  v-if="item.signature_image_url"
                  type="button"
                  @click="openImage(item.signature_image_url)"
                  class="border border-slate-600 rounded overflow-hidden hover:border-indigo-400"
                >
                  <img :src="item.signature_image_url" alt="Signature" class="w-10 h-10 object-cover" />
                </button>
                <span v-else>-</span>
              </td>
              <td class="text-right whitespace-nowrap">
                <Link :href="`/gmium/plugging/${item.id}/edit`" class="text-indigo-400 mr-2">Edit</Link>
                <button type="button" @click="destroy(item.id)" class="text-red-400">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>

        <div class="mt-4">
          <button @click="prev" :disabled="!pluggings.prev_page_url" class="px-3 py-1 bg-slate-700 rounded mr-2">Prev</button>
          <button @click="next" :disabled="!pluggings.next_page_url" class="px-3 py-1 bg-slate-700 rounded">Next</button>
        </div>
      </div>

      <div v-if="showImageModal" class="fixed inset-0 bg-black/80 flex items-center justify-center z-50 p-4" @click="closeImage">
        <div class="max-w-4xl max-h-[90vh]" @click.stop>
          <img :src="selectedImage" alt="Preview" class="max-h-[90vh] w-auto rounded" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Inertia } from '@inertiajs/inertia';
import AppLayout from '@/Layouts/AppLayout.vue';
import EnhancedDatePicker from '@/Components/EnhancedDatePicker.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
  pluggings: Object,
  filters: Object,
  report: Object,
  customers: {
    type: Array,
    default: () => [],
  },
});

const pluggings = reactive(props.pluggings);
const customers = props.customers || [];
const filters = reactive({
  tanggal: props.filters?.tanggal || '',
  customer: props.filters?.customer || '',
});

const showImageModal = ref(false);
const selectedImage = ref('');
const isTanggalFocused = ref(false);
const isCustomerFocused = ref(false);

const exportUrl = computed(() => {
  const params = new URLSearchParams();
  if (filters.tanggal) params.append('tanggal', filters.tanggal);
  if (filters.customer) params.append('customer', filters.customer);
  const qs = params.toString();
  return qs ? `/gmium/plugging-export?${qs}` : '/gmium/plugging-export';
});

function fetch(page = 1) {
  const params = {};
  if (filters.tanggal) params.tanggal = filters.tanggal;
  if (filters.customer) params.customer = filters.customer;
  if (page > 1) params.page = page;
  Inertia.get('/gmium/plugging', params, { preserveState: true, preserveScroll: true });
}

function next() {
  if (pluggings.next_page_url) fetch(pluggings.current_page + 1);
}

function prev() {
  if (pluggings.prev_page_url) fetch(pluggings.current_page - 1);
}

function resetFilter() {
  filters.tanggal = '';
  filters.customer = '';
  fetch(1);
}

function destroy(id) {
  if (!confirm('Delete data ini?')) return;
  Inertia.delete(`/gmium/plugging/${id}`);
}

function formatDate(value) {
  if (!value) return '-';
  return new Date(value).toLocaleDateString('id-ID');
}

function openImage(url) {
  selectedImage.value = url;
  showImageModal.value = true;
}

function closeImage() {
  showImageModal.value = false;
  selectedImage.value = '';
}
</script>
