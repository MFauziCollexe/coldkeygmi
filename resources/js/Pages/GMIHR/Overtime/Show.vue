<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
          <Link href="/overtime" class="text-slate-400 hover:text-white">
            ← Kembali
          </Link>
          <h2 class="text-2xl font-bold">Detail Permintaan Lembur</h2>
        </div>
      </div>

      <div class="rounded bg-slate-800 p-4 md:p-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
          <!-- Left Column -->
          <div>
            <h3 class="text-lg font-semibold mb-4 text-indigo-400">Informasi Permintaan</h3>
            
            <div class="space-y-3">
              <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-slate-400 text-sm">Tanggal Pengajuan</div>
                <div class="max-w-[62%] text-right">{{ formatDate(overtime.created_at) }}</div>
              </div>
              
              <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-slate-400 text-sm">Tanggal Lembur</div>
                <div class="max-w-[62%] text-right">{{ formatDate(overtime.overtime_date) }}</div>
              </div>
              
              <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-slate-400 text-sm">Jam Mulai</div>
                <div class="max-w-[62%] text-right">{{ overtime.start_time }}</div>
              </div>
              
              <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-slate-400 text-sm">Jam Selesai</div>
                <div class="max-w-[62%] text-right">{{ overtime.end_time }}</div>
              </div>
              
              <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-slate-400 text-sm">Jumlah Jam</div>
                <div class="max-w-[62%] text-right">{{ overtime.hours }} jam</div>
              </div>
              
              <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-slate-400 text-sm">Alasan</div>
                <div class="max-w-[62%] whitespace-pre-wrap text-right">{{ overtime.reason }}</div>
              </div>

              <div v-if="overtime.attachment_url">
                <div class="text-slate-400 text-sm">Attachment</div>
                <div class="mt-2 space-y-2">
                  <a
                    :href="overtime.attachment_url"
                    target="_blank"
                    rel="noopener"
                    class="text-indigo-400 hover:text-indigo-300 underline"
                  >
                    Lihat Attachment
                  </a>
                  <img
                    v-if="!isPdfAttachment(overtime)"
                    :src="overtime.attachment_url"
                    alt="Attachment lembur"
                    class="max-h-72 w-full rounded border border-slate-700 object-contain bg-slate-900/40"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Right Column -->
          <div>
            <h3 class="text-lg font-semibold mb-4 text-indigo-400">Informasi Karyawan</h3>
            
            <div class="space-y-3">
              <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-slate-400 text-sm">Nama Karyawan</div>
                <div class="max-w-[62%] text-right">{{ overtime.employee?.name || overtime.user?.name || '-' }}</div>
              </div>
              
              <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-slate-400 text-sm">Email</div>
                <div class="max-w-[62%] text-right">{{ overtime.user?.email || '-' }}</div>
              </div>
              
              <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-slate-400 text-sm">Department</div>
                <div class="max-w-[62%] text-right">{{ overtime.employee?.department?.name || overtime.user?.department?.name || '-' }}</div>
              </div>
              
              <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-slate-400 text-sm">Status</div>
                <span :class="getStatusClass(overtime.status)">
                  {{ overtime.status }}
                </span>
              </div>
              
              <div v-if="overtime.review_notes" class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-slate-400 text-sm">Catatan Review</div>
                <div class="max-w-[62%] whitespace-pre-wrap text-right">{{ overtime.review_notes }}</div>
              </div>
              
              <div v-if="overtime.reviewed_by" class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-slate-400 text-sm">Ditinjau Oleh</div>
                <div class="max-w-[62%] text-right">{{ overtime.reviewer?.name || '-' }}</div>
              </div>
              
              <div v-if="overtime.reviewed_at" class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-slate-400 text-sm">Tanggal Review</div>
                <div class="max-w-[62%] text-right">{{ formatDate(overtime.reviewed_at) }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Action buttons for manager/admin when status is pending -->
        <div v-if="overtime.status === 'pending' && (isAdmin || isManager)" class="mt-8 pt-6 border-t border-slate-700">
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
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  overtime: Object,
  isAdmin: Boolean,
  isManager: Boolean,
});

function isPdfAttachment(item) {
  const name = String(item?.attachment_original_name || '').toLowerCase();
  const url = String(item?.attachment_url || '').toLowerCase();
  return name.endsWith('.pdf') || url.includes('.pdf');
}

function formatDate(date) {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  });
}

function getStatusClass(status) {
  const classes = {
    'pending': 'bg-yellow-600 text-white px-3 py-1 rounded text-sm',
    'approved': 'bg-green-600 text-white px-3 py-1 rounded text-sm',
    'rejected': 'bg-red-600 text-white px-3 py-1 rounded text-sm',
  };
  return classes[status] || 'bg-slate-600 text-white px-3 py-1 rounded text-sm';
}

function submitApproval() {
  router.put(`/overtime/${props.overtime.id}`, {
    status: 'approved',
    review_notes: '',
  }, {
    onSuccess: () => {
      // Success - page will reload with updated data
    },
  });
}

function approveRequest() {
  Swal.fire({
    title: 'Konfirmasi',
    text: 'Apakah Anda yakin ingin menyetujui permintaan ini?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#16a34a',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Ya, Setujui',
    cancelButtonText: 'Batal'
  }).then((result) => {
    if (result.isConfirmed) {
      submitApproval();
    }
  });
}

function rejectRequest() {
  const notes = prompt('Masukkan alasan penolakan:');
  if (!notes) return;
  
  router.put(`/overtime/${props.overtime.id}`, {
    status: 'rejected',
    review_notes: notes,
  }, {
    onSuccess: () => {
      // Success - page will reload with updated data
    },
  });
}
</script>
