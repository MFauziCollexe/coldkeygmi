<template>
  <AppLayout>
    <div class="p-6 space-y-6">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h2 class="text-2xl font-bold">Exit Permit List</h2>
          <p class="text-slate-400 text-sm">Surat izin keluar dengan approval Security, HRD, dan Manager/Supervisor.</p>
        </div>
        <Link href="/gmi-visitor-permit/exit-permit/create" class="h-[44px] inline-flex items-center px-4 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-medium">
          Create Form
        </Link>
      </div>

      <div class="bg-slate-800 border border-slate-700 rounded-lg p-3">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
          <div class="md:col-span-4 relative">
            <input v-model="filters.search" placeholder=" " class="peer w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent" />
            <label class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1">Search No/Nama/Dept/Keperluan</label>
          </div>
          <div class="md:col-span-3 relative group">
            <EnhancedDatePicker v-model="filters.date" placeholder=" " input-class="w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent" />
            <label :class="['pointer-events-none absolute left-3 z-10 transition-all', filters.date ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2' : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2','group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2']">Tanggal</label>
          </div>
          <div class="md:col-span-2">
            <select v-model="filters.status" class="w-full h-[52px] rounded-lg bg-slate-800 border border-slate-700 px-3 text-slate-100">
              <option value="">Semua Status</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
            </select>
          </div>
          <div class="md:col-span-3 flex gap-2">
            <button class="h-[52px] px-5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white" @click="applyFilters">Filter</button>
            <button class="h-[52px] px-5 rounded-lg bg-slate-600 hover:bg-slate-500 text-white" @click="resetFilters">Reset</button>
          </div>
        </div>
      </div>

      <div class="bg-slate-800 border border-slate-700 rounded-lg p-4 overflow-x-auto">
        <table class="w-full text-sm min-w-[1600px]">
          <thead class="border-b border-slate-700 text-slate-400">
            <tr>
              <th class="text-left py-2 pr-3">No</th>
              <th class="text-left py-2 pr-3">Tanggal</th>
              <th class="text-left py-2 pr-3">Nama</th>
              <th class="text-left py-2 pr-3">Dept</th>
              <th class="text-left py-2 pr-3">Keperluan</th>
              <th class="text-left py-2 pr-3">Pukul</th>
              <th class="text-left py-2 pr-3">Security</th>
              <th class="text-left py-2 pr-3">HRD</th>
              <th class="text-left py-2 pr-3">Manager</th>
              <th class="text-left py-2 pr-3">Status</th>
              <th class="text-left py-2">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in exitPermits.data" :key="row.id" class="border-b border-slate-700/40">
              <td class="py-2 pr-3">{{ row.permit_number || '-' }}</td>
              <td class="py-2 pr-3">{{ formatDate(row.request_date) }}</td>
              <td class="py-2 pr-3">{{ row.employee_name }}</td>
              <td class="py-2 pr-3">{{ row.department_name || '-' }}</td>
              <td class="py-2 pr-3">{{ row.purpose }}</td>
              <td class="py-2 pr-3">{{ shortTime(row.time_out) }} - {{ shortTime(row.time_back) }}</td>
              <td class="py-2 pr-3"><span class="px-2 py-1 rounded text-xs font-semibold" :class="approvalBadge(row.security_status)">{{ row.security_status }}</span></td>
              <td class="py-2 pr-3"><span class="px-2 py-1 rounded text-xs font-semibold" :class="approvalBadge(row.hrd_status)">{{ row.hrd_status }}</span></td>
              <td class="py-2 pr-3"><span class="px-2 py-1 rounded text-xs font-semibold" :class="approvalBadge(row.manager_status)">{{ row.manager_status }}</span></td>
              <td class="py-2 pr-3"><span class="px-2 py-1 rounded text-xs font-semibold" :class="statusBadge(row.status)">{{ row.status }}</span></td>
              <td class="py-2">
                <div class="flex flex-wrap gap-2">
                  <template v-if="row.can_approve_security">
                    <button class="px-2 py-1 rounded bg-emerald-600 hover:bg-emerald-500 text-white text-xs" @click="approve(row.id, 'security', 'approved')">Approve Sec</button>
                    <button class="px-2 py-1 rounded bg-rose-600 hover:bg-rose-500 text-white text-xs" @click="approve(row.id, 'security', 'rejected')">Reject Sec</button>
                  </template>
                  <template v-if="row.can_approve_hrd">
                    <button class="px-2 py-1 rounded bg-emerald-600 hover:bg-emerald-500 text-white text-xs" @click="approve(row.id, 'hrd', 'approved')">Approve HRD</button>
                    <button class="px-2 py-1 rounded bg-rose-600 hover:bg-rose-500 text-white text-xs" @click="approve(row.id, 'hrd', 'rejected')">Reject HRD</button>
                  </template>
                  <template v-if="row.can_approve_manager">
                    <button class="px-2 py-1 rounded bg-emerald-600 hover:bg-emerald-500 text-white text-xs" @click="approve(row.id, 'manager', 'approved')">Approve Mgr</button>
                    <button class="px-2 py-1 rounded bg-rose-600 hover:bg-rose-500 text-white text-xs" @click="approve(row.id, 'manager', 'rejected')">Reject Mgr</button>
                  </template>
                  <span
                    v-if="!row.can_approve_security && !row.can_approve_hrd && !row.can_approve_manager"
                    class="text-xs text-slate-400"
                  >
                    Tidak ada aksi
                  </span>
                </div>
              </td>
            </tr>
            <tr v-if="!exitPermits.data?.length">
              <td colspan="11" class="py-8 text-center text-slate-400">Belum ada data surat izin keluar.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EnhancedDatePicker from '@/Components/EnhancedDatePicker.vue';

const props = defineProps({
  exitPermits: { type: Object, default: () => ({ data: [] }) },
  filters: { type: Object, default: () => ({}) },
  authUserId: { type: [Number, String, null], default: null },
  isSecurityApprover: { type: Boolean, default: false },
  isHrdApprover: { type: Boolean, default: false },
});

const filters = reactive({
  search: props.filters?.search || '',
  date: props.filters?.date || '',
  status: props.filters?.status || '',
});

function applyFilters() {
  router.get('/gmi-visitor-permit/exit-permit', {
    search: filters.search || undefined,
    date: filters.date || undefined,
    status: filters.status || undefined,
  }, { preserveState: true, replace: true });
}

function resetFilters() {
  filters.search = '';
  filters.date = '';
  filters.status = '';
  applyFilters();
}

function approve(id, role, decision) {
  router.post(`/gmi-visitor-permit/exit-permit/${id}/approve`, { role, decision }, { preserveScroll: true });
}

function formatDate(value) {
  if (!value) return '-';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return '-';
  return d.toLocaleDateString('id-ID');
}

function shortTime(value) {
  if (!value) return '-';
  return String(value).slice(0, 5);
}

function approvalBadge(status) {
  if (status === 'approved') return 'bg-emerald-700/30 text-emerald-300 border border-emerald-500/40';
  if (status === 'rejected') return 'bg-rose-700/30 text-rose-300 border border-rose-500/40';
  return 'bg-amber-700/30 text-amber-300 border border-amber-500/40';
}

function statusBadge(status) {
  if (status === 'approved') return 'bg-emerald-700/30 text-emerald-300 border border-emerald-500/40';
  if (status === 'rejected') return 'bg-rose-700/30 text-rose-300 border border-rose-500/40';
  return 'bg-sky-700/30 text-sky-300 border border-sky-500/40';
}
</script>
