<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
          <Link href="/leave-permission" class="text-slate-400 hover:text-white">
            ← Kembali
          </Link>
          <h2 class="text-2xl font-bold">Detail Permintaan</h2>
        </div>
        <Link
          v-if="canEditLeavePermission && ['pending', 'approved'].includes(leavePermission.status)"
          :href="`/leave-permission/${leavePermission.id}/edit`"
          class="inline-flex items-center justify-center rounded bg-amber-600 px-4 py-2 text-white hover:bg-amber-500"
        >
          Edit
        </Link>
      </div>

      <div class="rounded bg-slate-800 p-4 md:p-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
          <!-- Left Column -->
          <div>
            <h3 class="text-lg font-semibold mb-4 text-indigo-400">Informasi Permintaan</h3>
            
            <div class="space-y-3">
              <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-slate-400 text-sm">Jenis</div>
                <div class="max-w-[62%] text-right text-lg">{{ getTypeLabel(leavePermission.type) }}</div>
              </div>
              
              <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-slate-400 text-sm">Tanggal Pengajuan</div>
                <div class="max-w-[62%] text-right">{{ formatDate(leavePermission.created_at) }}</div>
              </div>
              
              <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-slate-400 text-sm">Tanggal Mulai</div>
                <div class="max-w-[62%] text-right">{{ formatDate(leavePermission.start_date) }}</div>
              </div>
              
              <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-slate-400 text-sm">Tanggal Selesai</div>
                <div class="max-w-[62%] text-right">{{ formatDate(leavePermission.end_date) }}</div>
              </div>
              
              <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-slate-400 text-sm">Jumlah Hari</div>
                <div class="max-w-[62%] text-right">{{ leavePermission.days }} hari</div>
              </div>
              
              <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-slate-400 text-sm">Alasan</div>
                <div class="max-w-[62%] whitespace-pre-wrap text-right">{{ leavePermission.reason }}</div>
              </div>

              <div>
                <div class="text-slate-400 text-sm">Attachment</div>
                <div v-if="leavePermission.attachments?.length" class="mt-2 space-y-2">
                  <div v-for="attachment in leavePermission.attachments" :key="attachment.path">
                    <button type="button" @click="openImage(attachment.url)" class="text-indigo-400 hover:text-indigo-300">
                      {{ attachment.name }}
                    </button>
                  </div>
                </div>
                <div v-else>-</div>
              </div>
            </div>
          </div>

          <!-- Right Column -->
          <div>
            <h3 class="text-lg font-semibold mb-4 text-indigo-400">Informasi Karyawan</h3>
            
              <div class="space-y-3">
                <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                  <div class="text-slate-400 text-sm">Nama Karyawan</div>
                  <div class="max-w-[62%] text-right">{{ leavePermission.employee?.name || leavePermission.user?.name || '-' }}</div>
                </div>
               
                <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                  <div class="text-slate-400 text-sm">Email</div>
                  <div class="max-w-[62%] text-right">{{ leavePermission.user?.email || '-' }}</div>
                </div>
               
                <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                  <div class="text-slate-400 text-sm">Department</div>
                  <div class="max-w-[62%] text-right">{{ leavePermission.employee?.department?.name || leavePermission.user?.department?.name || '-' }}</div>
                </div>
              
              <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-slate-400 text-sm">Status</div>
                <span :class="getStatusClass(leavePermission.status)">
                  {{ leavePermission.status }}
                </span>
              </div>
              
              <div v-if="leavePermission.review_notes" class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-slate-400 text-sm">Catatan Review</div>
                <div class="max-w-[62%] whitespace-pre-wrap text-right">{{ leavePermission.review_notes }}</div>
              </div>
              
              <div v-if="leavePermission.reviewed_by" class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-slate-400 text-sm">Ditinjau Oleh</div>
                <div class="max-w-[62%] text-right">{{ leavePermission.reviewer?.name || '-' }}</div>
              </div>
              
              <div v-if="leavePermission.reviewed_at" class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-slate-400 text-sm">Tanggal Review</div>
                <div class="max-w-[62%] text-right">{{ formatDate(leavePermission.reviewed_at) }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Action buttons for manager/admin when status is pending -->
        <div v-if="leavePermission.status === 'pending' && (isAdmin || isManager)" class="mt-8 pt-6 border-t border-slate-700">
          <h3 class="text-lg font-semibold mb-4">Tindakan</h3>
          <div class="flex flex-col gap-3 sm:flex-row">
            <button @click="rejectRequest" class="px-6 py-3 rounded bg-red-600 text-white hover:bg-red-700">
              Tolak Permintaan
            </button>
            <button @click="approveRequest" class="px-6 py-3 rounded bg-green-600 text-white hover:bg-green-700">
              Setuju Permintaan
            </button>
          </div>
        </div>

        <div v-if="isAdmin" class="mt-6 pt-4 border-t border-slate-700">
          <button @click="deleteRequest" class="w-full rounded bg-red-700 px-6 py-3 text-white hover:bg-red-800 sm:w-auto">
            Hapus Data
          </button>
        </div>
      </div>

      <!-- Image Preview Modal -->
      <div
        v-if="previewImage"
        class="fixed inset-0 z-[60] bg-black/70 flex items-center justify-center p-4"
        @click.self="closeImage"
      >
        <div class="max-w-4xl w-full bg-slate-900 border border-slate-700 rounded-lg p-3">
          <div class="flex justify-end mb-2">
            <button type="button" @click="closeImage" class="px-3 py-1 rounded bg-slate-700 text-slate-200">Tutup</button>
          </div>
          <img :src="previewImage" alt="Preview" class="w-full max-h-[75vh] object-contain rounded" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';
import { swalConfirm } from '@/Utils/swalConfirm';

const props = defineProps({
  leavePermission: Object,
  isAdmin: Boolean,
  isManager: Boolean,
  canEditLeavePermission: Boolean,
});
const previewImage = ref('');

function formatDate(date) {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  });
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
    'pending': 'bg-yellow-600 text-white px-3 py-1 rounded text-sm',
    'approved': 'bg-green-600 text-white px-3 py-1 rounded text-sm',
    'rejected': 'bg-red-600 text-white px-3 py-1 rounded text-sm',
  };
  return classes[status] || 'bg-slate-600 text-white px-3 py-1 rounded text-sm';
}

async function approveRequest() {
  const result = await Swal.fire({
    title: 'Konfirmasi',
    text: 'Apakah Anda yakin ingin menyetujui permintaan ini?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#16a34a',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Ya, Setujui',
    cancelButtonText: 'Batal'
  });

  if (!result.isConfirmed) return;
  
  router.put(`/leave-permission/${props.leavePermission.id}`, {
    status: 'approved',
    review_notes: '',
  }, {
    onSuccess: () => {
      // Success - page will reload with updated data
    },
  });
}

function rejectRequest() {
  const notes = prompt('Masukkan alasan penolakan:');
  if (!notes) return;
  
  router.put(`/leave-permission/${props.leavePermission.id}`, {
    status: 'rejected',
    review_notes: notes,
  }, {
    onSuccess: () => {
      // Success - page will reload with updated data
    },
  });
}

function openImage(url) {
  const u = String(url || '');
  if (!u) return;

  if (u.toLowerCase().includes('.pdf')) {
    window.open(u, '_blank', 'noopener');
    return;
  }

  previewImage.value = u;
}

function closeImage() {
  previewImage.value = '';
}

async function deleteRequest() {
  const ok = await swalConfirm({
    title: 'Hapus Data',
    text: 'Yakin ingin menghapus data ini?',
    confirmButtonText: 'Hapus',
    confirmButtonColor: '#dc2626',
  });
  if (!ok) return;

  router.delete(`/leave-permission/${props.leavePermission.id}`, {
    onSuccess: () => {
      router.get('/leave-permission');
    },
  });
}
</script>
