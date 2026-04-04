<template>
  <AppLayout>
    <div class="space-y-6 p-4 md:p-6">
      <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
          <h2 class="text-2xl font-bold">Visitor List</h2>
          <p class="text-slate-400 text-sm">Monitoring data tamu dan status kunjungan.</p>
        </div>
        <Link href="/gmi-visitor-permit/visitor-form/create" class="inline-flex h-[44px] items-center justify-center rounded-lg bg-indigo-600 px-4 text-white font-medium hover:bg-indigo-500">
          Create Form
        </Link>
      </div>

      <div class="rounded-lg border border-slate-700 bg-slate-800 p-3">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
          <div class="md:col-span-4 relative">
            <input v-model="filters.search" placeholder=" " class="peer w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent" />
            <label class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1">Search Nama/ID/Menemui/Keperluan</label>
          </div>
          <div class="md:col-span-3 relative group">
            <EnhancedDatePicker v-model="filters.date" placeholder=" " input-class="w-full h-[52px] px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent" />
            <label :class="['pointer-events-none absolute left-3 z-10 transition-all', filters.date ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2' : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2','group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2']">Tanggal</label>
          </div>
          <div class="md:col-span-2">
            <select v-model="filters.status" class="w-full h-[52px] rounded-lg bg-slate-800 border border-slate-700 px-3 text-slate-100">
              <option value="">Semua Status</option>
              <option v-for="st in statusOptions" :key="st.value" :value="st.value">{{ st.label }}</option>
            </select>
          </div>
          <div class="flex gap-2 md:col-span-3">
            <button class="h-[52px] w-full rounded-lg bg-indigo-600 px-5 text-white hover:bg-indigo-500" @click="applyFilters">Filter</button>
            <button class="h-[52px] w-full rounded-lg bg-slate-600 px-5 text-white hover:bg-slate-500" @click="resetFilters">Reset</button>
          </div>
        </div>
      </div>

      <div class="rounded-lg border border-slate-700 bg-slate-800 p-4">
        <div class="hidden overflow-x-auto lg:block">
          <table class="min-w-[1680px] w-full text-sm">
          <thead class="border-b border-slate-700 text-slate-400">
            <tr>
              <th class="text-left py-2 pr-3">Tanggal</th>
              <th class="text-left py-2 pr-3">Nama Visitor</th>
              <th class="text-left py-2 pr-3">From</th>
              <th class="text-left py-2 pr-3">Tanda Pengenal</th>
              <th class="text-left py-2 pr-3">Menemui</th>
              <th class="text-left py-2 pr-3">Keperluan</th>
              <th class="text-left py-2 pr-3">Waktu Perjanjian</th>
              <th class="text-left py-2 pr-3">Waktu Keluar</th>
              <th class="text-left py-2 pr-3">Approval Security</th>
              <th class="text-left py-2 pr-3">Approval Dituju</th>
              <th class="text-left py-2 pr-3">Status</th>
              <th class="text-left py-2 pr-3">Image</th>
              <th class="text-left py-2">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in visitors.data" :key="row.id" class="border-b border-slate-700/40">
              <td class="py-2 pr-3">{{ formatDate(row.visit_date) }}</td>
              <td class="py-2 pr-3">{{ row.visitor_name }}</td>
              <td class="py-2 pr-3">{{ row.from || '-' }}</td>
              <td class="py-2 pr-3">{{ row.identity_no || '-' }}</td>
              <td class="py-2 pr-3">{{ row.host_user?.name || row.host_name || '-' }}</td>
              <td class="py-2 pr-3">{{ row.purpose || '-' }}</td>
              <td class="py-2 pr-3">{{ row.appointment_time ? String(row.appointment_time).slice(0, 5) : '-' }}</td>
              <td class="py-2 pr-3">{{ row.check_out ? String(row.check_out).slice(0, 5) : '-' }}</td>
              <td class="py-2 pr-3">
                <span class="px-2 py-1 rounded text-xs font-semibold" :class="row.security_approved_at ? 'bg-emerald-700/30 text-emerald-300 border border-emerald-500/40' : 'bg-amber-700/30 text-amber-300 border border-amber-500/40'">
                  {{ row.security_approved_at ? 'Approved' : 'Pending' }}
                </span>
              </td>
              <td class="py-2 pr-3">
                <span class="px-2 py-1 rounded text-xs font-semibold" :class="row.host_approved_at ? 'bg-emerald-700/30 text-emerald-300 border border-emerald-500/40' : 'bg-amber-700/30 text-amber-300 border border-amber-500/40'">
                  {{ row.host_approved_at ? 'Approved' : 'Pending' }}
                </span>
              </td>
              <td class="py-2 pr-3">
                <div class="flex flex-col gap-1">
                  <span class="px-2 py-1 rounded text-xs font-semibold w-fit" :class="approvalClass(row.approval_status)">
                    {{ formatApprovalStatus(row.approval_status) }}
                  </span>
                  <span class="px-2 py-1 rounded text-xs font-semibold w-fit" :class="statusClass(row.status)">{{ row.status }}</span>
                </div>
              </td>
              <td class="py-2 pr-3">{{ row.attachments?.length || 0 }} file</td>
              <td class="py-2">
                <div class="flex gap-2">
                  <button
                    v-if="isSecurityApprover && !row.security_approved_at && row.approval_status !== 'approved'"
                    class="px-2 py-1 rounded bg-violet-600 hover:bg-violet-500 text-white text-xs"
                    @click="approve(row.id, 'security')"
                  >Approve Security</button>
                  <button
                    v-if="Number(authUserId) === Number(row.host_user_id) && !row.host_approved_at && row.approval_status !== 'approved'"
                    class="px-2 py-1 rounded bg-teal-600 hover:bg-teal-500 text-white text-xs"
                    @click="approve(row.id, 'host')"
                  >Approve Dituju</button>
                  <button
                    v-if="row.approval_status === 'approved' && row.status === 'Waiting'"
                    class="px-2 py-1 rounded bg-emerald-600 hover:bg-emerald-500 text-white text-xs"
                    @click="updateStatus(row.id, 'Checked In')"
                  >Check In</button>
                  <button
                    v-if="row.approval_status === 'approved' && row.status === 'Checked In'"
                    class="px-2 py-1 rounded bg-sky-600 hover:bg-sky-500 text-white text-xs"
                    @click="updateStatus(row.id, 'Checked Out')"
                  >Check Out</button>
                  <button
                    v-if="row.status !== 'Checked Out' && row.status !== 'Cancelled'"
                    class="px-2 py-1 rounded bg-rose-600 hover:bg-rose-500 text-white text-xs"
                    @click="updateStatus(row.id, 'Cancelled')"
                  >Cancel</button>
                </div>
              </td>
            </tr>
            <tr v-if="!visitors.data?.length">
              <td colspan="13" class="py-8 text-center text-slate-400">Belum ada data visitor.</td>
            </tr>
          </tbody>
          </table>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-700 lg:hidden">
          <div v-if="!visitors.data?.length" class="bg-slate-900/30 px-4 py-8 text-center text-slate-400">
            Belum ada data visitor.
          </div>
          <div
            v-for="row in visitors.data"
            :key="`mobile-${row.id}`"
            class="border-b border-slate-700/60 bg-slate-900/30 p-4 last:border-b-0"
          >
            <div class="mb-3 flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="font-semibold text-white">{{ row.visitor_name }}</div>
                <div class="text-sm text-slate-400">{{ formatDate(row.visit_date) }}</div>
              </div>
              <div class="text-right text-xs text-slate-400">
                {{ row.attachments?.length || 0 }} file
              </div>
            </div>

            <div class="space-y-2 text-sm">
              <div class="flex items-start justify-between gap-4">
                <span class="text-slate-400">From</span>
                <span class="max-w-[62%] text-right">{{ row.from || '-' }}</span>
              </div>
              <div class="flex items-start justify-between gap-4">
                <span class="text-slate-400">Tanda Pengenal</span>
                <span class="max-w-[62%] text-right">{{ row.identity_no || '-' }}</span>
              </div>
              <div class="flex items-start justify-between gap-4">
                <span class="text-slate-400">Menemui</span>
                <span class="max-w-[62%] text-right">{{ row.host_user?.name || row.host_name || '-' }}</span>
              </div>
              <div class="flex items-start justify-between gap-4">
                <span class="text-slate-400">Keperluan</span>
                <span class="max-w-[62%] text-right">{{ row.purpose || '-' }}</span>
              </div>
              <div class="flex items-start justify-between gap-4">
                <span class="text-slate-400">Perjanjian</span>
                <span class="text-right">{{ row.appointment_time ? String(row.appointment_time).slice(0, 5) : '-' }}</span>
              </div>
              <div class="flex items-start justify-between gap-4">
                <span class="text-slate-400">Keluar</span>
                <span class="text-right">{{ row.check_out ? String(row.check_out).slice(0, 5) : '-' }}</span>
              </div>
            </div>

            <div class="mt-3 flex flex-wrap gap-2">
              <span class="px-2 py-1 rounded text-xs font-semibold" :class="row.security_approved_at ? 'bg-emerald-700/30 text-emerald-300 border border-emerald-500/40' : 'bg-amber-700/30 text-amber-300 border border-amber-500/40'">
                Security: {{ row.security_approved_at ? 'Approved' : 'Pending' }}
              </span>
              <span class="px-2 py-1 rounded text-xs font-semibold" :class="row.host_approved_at ? 'bg-emerald-700/30 text-emerald-300 border border-emerald-500/40' : 'bg-amber-700/30 text-amber-300 border border-amber-500/40'">
                Dituju: {{ row.host_approved_at ? 'Approved' : 'Pending' }}
              </span>
              <span class="px-2 py-1 rounded text-xs font-semibold" :class="approvalClass(row.approval_status)">
                {{ formatApprovalStatus(row.approval_status) }}
              </span>
              <span class="px-2 py-1 rounded text-xs font-semibold" :class="statusClass(row.status)">{{ row.status }}</span>
            </div>

            <div class="mt-4 flex flex-col gap-2">
              <button
                v-if="isSecurityApprover && !row.security_approved_at && row.approval_status !== 'approved'"
                class="rounded bg-violet-600 px-3 py-2 text-sm text-white"
                @click="approve(row.id, 'security')"
              >Approve Security</button>
              <button
                v-if="Number(authUserId) === Number(row.host_user_id) && !row.host_approved_at && row.approval_status !== 'approved'"
                class="rounded bg-teal-600 px-3 py-2 text-sm text-white"
                @click="approve(row.id, 'host')"
              >Approve Dituju</button>
              <button
                v-if="row.approval_status === 'approved' && row.status === 'Waiting'"
                class="rounded bg-emerald-600 px-3 py-2 text-sm text-white"
                @click="updateStatus(row.id, 'Checked In')"
              >Check In</button>
              <button
                v-if="row.approval_status === 'approved' && row.status === 'Checked In'"
                class="rounded bg-sky-600 px-3 py-2 text-sm text-white"
                @click="updateStatus(row.id, 'Checked Out')"
              >Check Out</button>
              <button
                v-if="row.status !== 'Checked Out' && row.status !== 'Cancelled'"
                class="rounded bg-rose-600 px-3 py-2 text-sm text-white"
                @click="updateStatus(row.id, 'Cancelled')"
              >Cancel</button>
            </div>
          </div>
        </div>
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
  visitors: { type: Object, default: () => ({ data: [] }) },
  filters: { type: Object, default: () => ({}) },
  statusOptions: { type: Array, default: () => [] },
  authUserId: { type: [Number, String, null], default: null },
  isSecurityApprover: { type: Boolean, default: false },
});

const filters = reactive({
  search: props.filters?.search || '',
  date: props.filters?.date || '',
  status: props.filters?.status || '',
});

function applyFilters() {
  router.get('/gmi-visitor-permit/visitor-form', {
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

function updateStatus(id, status) {
  router.post(`/gmi-visitor-permit/visitor-form/${id}/status`, { status }, { preserveScroll: true });
}

function approve(id, role) {
  router.post(`/gmi-visitor-permit/visitor-form/${id}/approve`, { role }, { preserveScroll: true });
}

function formatDate(value) {
  if (!value) return '-';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return '-';
  return d.toLocaleDateString('id-ID');
}

function formatApprovalStatus(status) {
  if (status === 'approved') return 'Approved';
  if (status === 'partially_approved') return 'Partial';
  return 'Pending Approval';
}

function approvalClass(status) {
  if (status === 'approved') return 'bg-emerald-700/30 text-emerald-300 border border-emerald-500/40';
  if (status === 'partially_approved') return 'bg-sky-700/30 text-sky-300 border border-sky-500/40';
  return 'bg-amber-700/30 text-amber-300 border border-amber-500/40';
}

function statusClass(status) {
  if (status === 'Checked Out') return 'bg-emerald-700/30 text-emerald-300 border border-emerald-500/40';
  if (status === 'Checked In') return 'bg-sky-700/30 text-sky-300 border border-sky-500/40';
  if (status === 'Cancelled') return 'bg-rose-700/30 text-rose-300 border border-rose-500/40';
  return 'bg-amber-700/30 text-amber-300 border border-amber-500/40';
}
</script>
