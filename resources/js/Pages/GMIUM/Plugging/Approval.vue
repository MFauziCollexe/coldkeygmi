<template>
  <AppLayout>
    <div class="w-full">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold">Plugging Approval</h2>
      </div>

      <div class="bg-slate-800 rounded p-4 mb-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
          <div class="relative group">
            <EnhancedDatePicker
              v-model="filters.tanggal"
              placeholder=""
              input-class="w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent"
            />
            <label
              :class="[
                'pointer-events-none absolute left-3 z-10 transition-all',
                (filters.tanggal
                  ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                  : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
                'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
              ]"
            >
              Tanggal
            </label>
          </div>
          <div class="relative">
            <input
              v-model="filters.customer"
              type="text"
              placeholder=" "
              class="peer w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent"
            />
            <label
              class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1"
            >
              Customer
            </label>
          </div>
          <div class="md:col-span-2 flex items-end gap-2">
            <button @click="fetch()" class="h-[54px] px-5 rounded-lg bg-indigo-600 text-white">Filter</button>
            <button @click="resetFilter" class="h-[54px] px-5 rounded-lg bg-slate-700 text-white">Reset</button>
          </div>
        </div>
      </div>

      <div class="bg-slate-800 rounded p-4 overflow-x-auto">
        <table class="w-full table-auto min-w-[1100px]">
          <thead>
            <tr class="text-left text-slate-400 text-sm">
              <th class="py-2">Tanggal</th>
              <th>Customer</th>
              <th>No Container / No Polisi</th>
              <th>Jam Mulai</th>
              <th>Jam Selesai</th>
              <th>Status</th>
              <th>User</th>
              <th class="text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!pluggings.data || pluggings.data.length === 0" class="border-t border-slate-700">
              <td colspan="8" class="py-8 text-center text-slate-400">Tidak ada data untuk approval.</td>
            </tr>
            <tr v-for="item in pluggings.data" :key="item.id" class="border-t border-slate-700 text-sm">
              <td class="py-3">{{ formatDate(item.tanggal) }}</td>
              <td>{{ item.customer }}</td>
              <td>{{ item.no_container_no_polisi }}</td>
              <td>{{ item.jam_mulai || '-' }}</td>
              <td>{{ item.jam_selesai || '-' }}</td>
              <td>
                <span class="px-2 py-1 rounded text-white text-xs bg-yellow-600">
                  {{ item.status }}
                </span>
              </td>
              <td>{{ item.user?.name || '-' }}</td>
              <td class="text-right">
                <button v-if="canApprove" type="button" class="px-3 py-1 rounded bg-green-600 text-white hover:bg-green-700" @click="openApprove(item)">
                  Approve
                </button>
                <span v-else class="text-xs text-slate-400">View only</span>
              </td>
            </tr>
          </tbody>
        </table>

        <div class="mt-4">
          <Pagination :paginator="pluggings" :onPageChange="fetch" />
        </div>
      </div>

      <div v-if="showApproveModal" class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4" @click="closeApprove">
        <div class="w-full max-w-lg bg-slate-900 border border-slate-700 rounded-lg p-5" @click.stop>
          <h3 class="text-lg font-semibold mb-4">Approve Plugging</h3>

          <div class="space-y-3">
            <div>
              <label class="block text-sm mb-1">PIC Customer</label>
              <input v-model="approveForm.pic_customer" type="text" class="w-full px-3 py-2 rounded bg-slate-800 border border-slate-700" />
              <div v-if="approveForm.errors.pic_customer" class="text-red-400 text-sm mt-1">{{ approveForm.errors.pic_customer }}</div>
            </div>

            <div>
              <label class="block text-sm mb-1">Signature</label>
              <input type="file" accept="image/*" @change="onSignatureChange" class="w-full text-sm file:mr-3 file:px-3 file:py-2 file:rounded file:border-0 file:bg-slate-700 file:text-white" />
              <div v-if="approveForm.errors.signature_image" class="text-red-400 text-sm mt-1">{{ approveForm.errors.signature_image }}</div>
            </div>
          </div>

          <div class="flex justify-end gap-2 mt-5">
            <button type="button" class="px-4 py-2 rounded bg-slate-700 text-white" @click="closeApprove">Batal</button>
            <button type="button" class="px-4 py-2 rounded bg-green-600 text-white hover:bg-green-700" :disabled="approveForm.processing || !selectedId" @click="submitApprove">
              Simpan Approval
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive, ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EnhancedDatePicker from '@/Components/EnhancedDatePicker.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
  pluggings: Object,
  filters: Object,
  canApprove: {
    type: Boolean,
    default: false,
  },
});

const pluggings = computed(() => props.pluggings);
const filters = reactive({
  tanggal: props.filters?.tanggal || '',
  customer: props.filters?.customer || '',
});

const showApproveModal = ref(false);
const selectedId = ref(null);
const canApprove = props.canApprove === true;

const approveForm = useForm({
  pic_customer: '',
  signature_image: null,
});

function fetch(page = 1) {
  const params = {};
  if (filters.tanggal) params.tanggal = filters.tanggal;
  if (filters.customer) params.customer = filters.customer;
  if (page > 1) params.page = page;
  router.get('/gmium/plugging/approval', params, { preserveState: true, preserveScroll: true });
}

function next() {
  if (pluggings.value.next_page_url) fetch(pluggings.value.current_page + 1);
}

function prev() {
  if (pluggings.value.prev_page_url) fetch(pluggings.value.current_page - 1);
}

function resetFilter() {
  filters.tanggal = '';
  filters.customer = '';
  fetch(1);
}

function openApprove(item) {
  selectedId.value = item.id;
  approveForm.reset();
  approveForm.clearErrors();
  showApproveModal.value = true;
}

function closeApprove() {
  showApproveModal.value = false;
  selectedId.value = null;
}

function onSignatureChange(event) {
  approveForm.signature_image = event.target.files?.[0] || null;
}

function submitApprove() {
  if (!selectedId.value) return;

  approveForm.transform((data) => ({ ...data, _method: 'put' })).post(`/gmium/plugging/${selectedId.value}/approve`, {
    forceFormData: true,
    onSuccess: () => {
      closeApprove();
    },
  });
}

function formatDate(value) {
  if (!value) return '-';
  return new Date(value).toLocaleDateString('id-ID');
}
</script>
