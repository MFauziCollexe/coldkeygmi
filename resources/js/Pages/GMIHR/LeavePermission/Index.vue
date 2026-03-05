<template>
  <AppLayout>
    <div class="p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold">Leave & Permission</h2>
        <Link href="/leave-permission/create" class="bg-indigo-600 px-4 py-2 rounded text-white">
          + Ajukan Permintaan
        </Link>
      </div>

      <!-- Filters -->
      <div class="bg-slate-800 border border-slate-700 rounded-lg p-3 mb-4">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
          <div class="md:col-span-3 relative">
            <input
              v-model="filters.search"
              @keyup.enter="fetchData"
              placeholder=" "
              class="peer w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent"
            />
            <label class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1">
              Cari Karyawan
            </label>
          </div>

          <div class="md:col-span-2 relative group">
            <SearchableSelect
              v-model="filters.status"
              :options="statusOptions"
              option-value="value"
              option-label="label"
              placeholder=" "
              empty-label="Status"
              input-class="w-full h-[52px] pl-3 pr-10 pt-5 pb-2 !bg-slate-800 !border-slate-700 rounded-lg text-slate-100 placeholder-transparent"
              button-class="h-[52px] border-0 border-l !border-slate-700 rounded-r-lg !bg-slate-800 text-slate-100"
              @update:modelValue="fetchData"
            />
            <label
              :class="[
                'pointer-events-none absolute left-3 z-10 transition-all',
                (filters.status
                  ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                  : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
                'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
              ]"
            >
              Status
            </label>
          </div>

          <div v-if="isAdmin" class="md:col-span-3 relative group">
            <SearchableSelect
              v-model="filters.department_id"
              :options="departments"
              option-value="id"
              option-label="name"
              placeholder=" "
              empty-label="Semua Department"
              input-class="w-full h-[52px] pl-3 pr-10 pt-5 pb-2 !bg-slate-800 !border-slate-700 rounded-lg text-slate-100 placeholder-transparent"
              button-class="h-[52px] border-0 border-l !border-slate-700 rounded-r-lg !bg-slate-800 text-slate-100"
              @update:modelValue="fetchData"
            />
            <label
              :class="[
                'pointer-events-none absolute left-3 z-10 transition-all',
                (filters.department_id
                  ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                  : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
                'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
              ]"
            >
              Department
            </label>
          </div>

        </div>
      </div>

      <!-- Table -->
      <div class="bg-slate-800 rounded p-4">
        <table class="w-full table-auto">
          <thead>
            <tr class="text-left text-slate-400 border-b border-slate-700">
              <th class="py-3">No</th>
              <th>Tanggal Pengajuan</th>
              <th>Karyawan</th>
              <th>Department</th>
              <th>Tanggal Mulai</th>
              <th>Tanggal Selesai</th>
              <th>Jumlah Hari</th>
              <th>Alasan</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in leavePermissions.data" :key="item.id" class="border-t border-slate-700">
              <td class="py-3">{{ index + 1 }}</td>
              <td>{{ formatDate(item.created_at) }}</td>
              <td>{{ item.user?.name || '-' }}</td>
              <td>{{ item.user?.department?.name || '-' }}</td>
              <td>{{ formatDate(item.start_date) }}</td>
              <td>{{ formatDate(item.end_date) }}</td>
              <td>{{ item.days }}</td>
              <td>{{ item.reason }}</td>
              <td>
                <span :class="getStatusClass(item.status)">
                  {{ item.status }}
                </span>
              </td>
              <td>
                <Link :href="`/leave-permission/${item.id}`" class="text-indigo-400 hover:text-indigo-300">
                  Detail
                </Link>
              </td>
            </tr>
            <tr v-if="!leavePermissions.data || leavePermissions.data.length === 0">
              <td colspan="10" class="py-8 text-center text-slate-400">
                Tidak ada data
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div class="flex items-center justify-between mt-4">
          <div class="text-sm text-slate-400">
            Menampilkan {{ leavePermissions.data?.length || 0 }} dari {{ leavePermissions.total || 0 }} data
          </div>
          <div class="flex gap-2">
            <button 
              @click="prevPage" 
              :disabled="!leavePermissions.prev_page_url"
              class="px-3 py-1 bg-slate-700 rounded text-sm disabled:opacity-50"
            >
              Previous
            </button>
            <button 
              @click="nextPage" 
              :disabled="!leavePermissions.next_page_url"
              class="px-3 py-1 bg-slate-700 rounded text-sm disabled:opacity-50"
            >
              Next
            </button>
          </div>
        </div>
      </div>

      <!-- Create Modal -->
      <LeavePermissionForm 
        v-if="showCreateModal" 
        title="Ajukan Permintaan"
        @close="showCreateModal = false"
        @success="fetchData"
      />

      <!-- Detail Modal -->
      <div v-if="showDetailModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-transparent rounded-lg p-6 w-full max-w-lg">
          <h3 class="text-xl font-bold mb-4">Detail Permintaan</h3>
          
          <div class="space-y-3">
            <div class="flex justify-between">
              <span class="text-slate-400">Jenis:</span>
              <span>{{ getTypeLabel(selectedItem?.type) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-400">Karyawan:</span>
              <span>{{ selectedItem?.user?.name }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-400">Department:</span>
              <span>{{ selectedItem?.user?.department?.name }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-400">Tanggal Mulai:</span>
              <span>{{ formatDate(selectedItem?.start_date) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-400">Tanggal Selesai:</span>
              <span>{{ formatDate(selectedItem?.end_date) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-400">Jumlah Hari:</span>
              <span>{{ selectedItem?.days }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-400">Alasan:</span>
              <span>{{ selectedItem?.reason }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-400">Status:</span>
              <span :class="getStatusClass(selectedItem?.status)">{{ selectedItem?.status }}</span>
            </div>
            <div v-if="selectedItem?.review_notes" class="flex justify-between">
              <span class="text-slate-400">Catatan:</span>
              <span>{{ selectedItem?.review_notes }}</span>
            </div>
          </div>

          <!-- Action buttons for manager/admin when status is pending -->
          <div v-if="selectedItem?.status === 'pending' && (isAdmin || isManager)" class="mt-4 pt-4 border-t border-slate-700">
            <div class="flex gap-2 justify-end">
              <button @click="rejectRequest(selectedItem)" class="px-4 py-2 rounded bg-red-600 text-white">
                Tolak
              </button>
              <button @click="approveRequest(selectedItem)" class="px-4 py-2 rounded bg-green-600 text-white">
                Setuju
              </button>
            </div>
          </div>

          <div class="flex justify-end gap-2 mt-6">
            <button @click="showDetailModal = false" class="px-4 py-2 rounded bg-slate-700 text-slate-300">
              Tutup
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Inertia } from '@inertiajs/inertia';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
  leavePermissions: Object,
  filters: Object,
  departments: Array,
  isAdmin: Boolean,
  isManager: Boolean,
});

const filters = reactive({
  search: props.filters?.search || '',
  status: props.filters?.status || '',
  type: props.filters?.type || '',
  department_id: props.filters?.department_id || '',
});

const showCreateModal = ref(false);
const showDetailModal = ref(false);
const selectedItem = ref(null);
const statusOptions = [
  { value: 'pending', label: 'Pending' },
  { value: 'approved', label: 'Approved' },
  { value: 'rejected', label: 'Rejected' },
];

function fetchData() {
  const params = {};
  if (filters.search) params.search = filters.search;
  if (filters.status) params.status = filters.status;
  if (filters.type) params.type = filters.type;
  if (filters.department_id) params.department_id = filters.department_id;
  Inertia.get('/leave-permission', params, { 
    preserveState: true, 
    preserveScroll: true 
  });
}

function goToPage(pageNum) {
  const params = { 
    page: pageNum,
  };
  if (filters.search) params.search = filters.search;
  if (filters.status) params.status = filters.status;
  if (filters.type) params.type = filters.type;
  if (filters.department_id) params.department_id = filters.department_id;
  Inertia.get('/leave-permission', params, { 
    preserveState: true, 
    preserveScroll: true 
  });
}

function prevPage() {
  if (props.leavePermissions.prev_page_url) {
    goToPage(props.leavePermissions.current_page - 1);
  }
}

function nextPage() {
  if (props.leavePermissions.next_page_url) {
    goToPage(props.leavePermissions.current_page + 1);
  }
}

function formatDate(date) {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('id-ID');
}

function getTypeLabel(type) {
  const labels = {
    'cuti': 'Cuti',
    'izin': 'Izin',
    'sakit': 'Sakit',
    'dinas_luar': 'Dinas Luar',
  };
  return labels[type] || type;
}

function getStatusClass(status) {
  const classes = {
    'pending': 'bg-yellow-600 text-white px-2 py-1 rounded text-xs',
    'approved': 'bg-green-600 text-white px-2 py-1 rounded text-xs',
    'rejected': 'bg-red-600 text-white px-2 py-1 rounded text-xs',
  };
  return classes[status] || 'bg-slate-600 text-white px-2 py-1 rounded text-xs';
}

function viewDetail(item) {
  selectedItem.value = item;
  showDetailModal.value = true;
}

function approveRequest(item) {
  if (!confirm('Apakah Anda yakin ingin menyetujui permintaan ini?')) return;
  
  Inertia.put(`/leave-permission/${item.id}`, {
    status: 'approved',
    review_notes: '',
  }, {
    onSuccess: () => {
      showDetailModal.value = false;
      fetchData();
    },
  });
}

function rejectRequest(item) {
  const notes = prompt('Masukkan alasan penolakan:');
  if (!notes) return;
  
  Inertia.put(`/leave-permission/${item.id}`, {
    status: 'rejected',
    review_notes: notes,
  }, {
    onSuccess: () => {
      showDetailModal.value = false;
      fetchData();
    },
  });
}
</script>
