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
        <button
          v-if="isAdmin"
          type="button"
          class="rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-500"
          @click="openEditForm"
        >
          Edit
        </button>
      </div>

      <div class="rounded bg-slate-800 p-4 md:p-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
          <div>
            <h3 class="mb-4 text-lg font-semibold text-indigo-400">Informasi Permintaan</h3>

            <div class="space-y-3">
              <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-sm text-slate-400">Tanggal Pengajuan</div>
                <div class="max-w-[62%] text-right">{{ formatDate(overtime.created_at) }}</div>
              </div>

              <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-sm text-slate-400">Tanggal Lembur</div>
                <div class="max-w-[62%] text-right">{{ formatDate(overtime.overtime_date) }}</div>
              </div>

              <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-sm text-slate-400">Jam Mulai</div>
                <div class="max-w-[62%] text-right">{{ overtime.start_time }}</div>
              </div>

              <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-sm text-slate-400">Jam Selesai</div>
                <div class="max-w-[62%] text-right">{{ overtime.end_time }}</div>
              </div>

              <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-sm text-slate-400">Jumlah Jam</div>
                <div class="max-w-[62%] text-right">{{ overtime.hours }} jam</div>
              </div>

              <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-sm text-slate-400">Alasan</div>
                <div class="max-w-[62%] whitespace-pre-wrap text-right">{{ overtime.reason }}</div>
              </div>

              <div v-if="overtime.attachment_url">
                <div class="text-sm text-slate-400">Attachment</div>
                <div class="mt-2 space-y-2">
                  <a
                    :href="overtime.attachment_url"
                    target="_blank"
                    rel="noopener"
                    class="text-indigo-400 underline hover:text-indigo-300"
                  >
                    Lihat Attachment
                  </a>
                  <img
                    v-if="!isPdfAttachment(overtime)"
                    :src="overtime.attachment_url"
                    alt="Attachment lembur"
                    class="max-h-72 w-full rounded border border-slate-700 bg-slate-900/40 object-contain"
                  />
                </div>
              </div>
            </div>
          </div>

          <div>
            <h3 class="mb-4 text-lg font-semibold text-indigo-400">Informasi Karyawan</h3>

            <div class="space-y-3">
              <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-sm text-slate-400">Nama Karyawan</div>
                <div class="max-w-[62%] text-right">{{ overtime.employee?.name || overtime.user?.name || '-' }}</div>
              </div>

              <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-sm text-slate-400">Email</div>
                <div class="max-w-[62%] text-right">{{ overtime.user?.email || '-' }}</div>
              </div>

              <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-sm text-slate-400">Department</div>
                <div class="max-w-[62%] text-right">{{ overtime.employee?.department?.name || overtime.user?.department?.name || '-' }}</div>
              </div>

              <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-sm text-slate-400">Status</div>
                <span :class="getStatusClass(overtime.status)">
                  {{ overtime.status }}
                </span>
              </div>

              <div v-if="overtime.review_notes" class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-sm text-slate-400">Catatan Review</div>
                <div class="max-w-[62%] whitespace-pre-wrap text-right">{{ overtime.review_notes }}</div>
              </div>

              <div v-if="overtime.reviewed_by" class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-sm text-slate-400">Ditinjau Oleh</div>
                <div class="max-w-[62%] text-right">{{ overtime.reviewer?.name || '-' }}</div>
              </div>

              <div v-if="overtime.reviewed_at" class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                <div class="text-sm text-slate-400">Tanggal Review</div>
                <div class="max-w-[62%] text-right">{{ formatDate(overtime.reviewed_at) }}</div>
              </div>
            </div>
          </div>
        </div>

        <div v-if="overtime.status === 'pending' && (isAdmin || isManager)" class="mt-8 border-t border-slate-700 pt-6">
          <h3 class="mb-4 text-lg font-semibold">Tindakan</h3>
          <div class="flex flex-col gap-3 sm:flex-row">
            <button @click="rejectRequest" class="rounded bg-red-600 px-6 py-3 text-white hover:bg-red-700">
              Tolak Permintaan
            </button>
            <button @click="approveRequest" class="rounded bg-green-600 px-6 py-3 text-white hover:bg-green-700">
              Setuju Permintaan
            </button>
          </div>
        </div>
      </div>

      <div v-if="isAdmin && isEditing" class="mt-6 rounded bg-slate-800 p-4 md:p-6">
        <div class="mb-4 flex items-center justify-between gap-3">
          <h3 class="text-lg font-semibold text-indigo-400">Edit Detail Overtime</h3>
          <button
            type="button"
            class="rounded bg-slate-700 px-3 py-2 text-slate-200 hover:bg-slate-600"
            @click="cancelEdit"
          >
            Batal
          </button>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm text-slate-300">Tanggal Lembur</label>
            <input
              v-model="editForm.overtime_date"
              type="date"
              class="w-full rounded border border-slate-700 bg-slate-900 px-3 py-2 text-white"
            />
            <div v-if="editForm.errors.overtime_date" class="mt-1 text-sm text-red-400">{{ editForm.errors.overtime_date }}</div>
          </div>

          <div>
            <label class="mb-1 block text-sm text-slate-300">Jam Mulai</label>
            <input
              v-model="editForm.start_time"
              type="time"
              class="w-full rounded border border-slate-700 bg-slate-900 px-3 py-2 text-white"
            />
            <div v-if="editForm.errors.start_time" class="mt-1 text-sm text-red-400">{{ editForm.errors.start_time }}</div>
          </div>

          <div>
            <label class="mb-1 block text-sm text-slate-300">Jam Selesai</label>
            <input
              v-model="editForm.end_time"
              type="time"
              class="w-full rounded border border-slate-700 bg-slate-900 px-3 py-2 text-white"
            />
            <div v-if="editForm.errors.end_time" class="mt-1 text-sm text-red-400">{{ editForm.errors.end_time }}</div>
          </div>

          <div>
            <label class="mb-1 block text-sm text-slate-300">Attachment Baru</label>
            <input
              type="file"
              accept="image/*,application/pdf,.pdf"
              class="w-full rounded border border-slate-700 bg-slate-900 px-3 py-2 text-slate-200"
              @change="onAttachmentChange"
            />
            <div v-if="editForm.errors.attachment" class="mt-1 text-sm text-red-400">{{ editForm.errors.attachment }}</div>
            <div class="mt-1 text-xs text-slate-400">Opsional, upload file baru untuk mengganti attachment lama.</div>
          </div>

          <div class="md:col-span-2">
            <label class="mb-1 block text-sm text-slate-300">Alasan</label>
            <textarea
              v-model="editForm.reason"
              rows="4"
              class="w-full rounded border border-slate-700 bg-slate-900 px-3 py-2 text-white"
            ></textarea>
            <div v-if="editForm.errors.reason" class="mt-1 text-sm text-red-400">{{ editForm.errors.reason }}</div>
          </div>

          <label v-if="overtime.attachment_url" class="inline-flex items-center gap-2 text-sm text-slate-300 md:col-span-2">
            <input
              v-model="editForm.remove_attachment"
              type="checkbox"
              class="rounded border-slate-600 bg-slate-900 text-indigo-500 focus:ring-indigo-500"
            />
            Hapus attachment lama
          </label>
        </div>

        <div class="mt-5 flex flex-col gap-2 sm:flex-row sm:justify-end">
          <button
            type="button"
            class="rounded bg-slate-700 px-4 py-2 text-slate-200 hover:bg-slate-600"
            @click="cancelEdit"
          >
            Batal
          </button>
          <button
            type="button"
            class="rounded bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-500 disabled:opacity-60"
            :disabled="editForm.processing"
            @click="submitEdit"
          >
            {{ editForm.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { swalConfirm } from '@/Utils/swalConfirm';

const props = defineProps({
  overtime: Object,
  isAdmin: Boolean,
  isManager: Boolean,
});

const isEditing = ref(false);
const editForm = useForm(buildEditPayload());

function buildEditPayload() {
  return {
    _method: 'put',
    action: 'edit',
    overtime_date: toDateInputValue(props.overtime?.overtime_date),
    start_time: normalizeTimeValue(props.overtime?.start_time),
    end_time: normalizeTimeValue(props.overtime?.end_time),
    reason: String(props.overtime?.reason || ''),
    attachment: null,
    remove_attachment: false,
  };
}

function toDateInputValue(value) {
  const normalized = String(value || '').trim();
  if (!normalized) return '';
  return normalized.length >= 10 ? normalized.slice(0, 10) : normalized;
}

function normalizeTimeValue(value) {
  return String(value || '').trim().slice(0, 5);
}

function openEditForm() {
  isEditing.value = true;
  editForm.defaults(buildEditPayload());
  editForm.reset();
  editForm.clearErrors();
}

function cancelEdit() {
  isEditing.value = false;
  editForm.defaults(buildEditPayload());
  editForm.reset();
  editForm.clearErrors();
}

function onAttachmentChange(event) {
  editForm.attachment = event?.target?.files?.[0] || null;
}

function submitEdit() {
  editForm.post(`/overtime/${props.overtime.id}`, {
    forceFormData: true,
    onSuccess: () => {
      isEditing.value = false;
    },
  });
}

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
    year: 'numeric',
  });
}

function getStatusClass(status) {
  const classes = {
    pending: 'rounded bg-yellow-600 px-3 py-1 text-sm text-white',
    approved: 'rounded bg-green-600 px-3 py-1 text-sm text-white',
    rejected: 'rounded bg-red-600 px-3 py-1 text-sm text-white',
  };
  return classes[status] || 'rounded bg-slate-600 px-3 py-1 text-sm text-white';
}

function submitApproval() {
  router.put(`/overtime/${props.overtime.id}`, {
    status: 'approved',
    review_notes: '',
  });
}

async function approveRequest() {
  const ok = await swalConfirm({
    title: 'Konfirmasi',
    text: 'Apakah Anda yakin ingin menyetujui permintaan ini?',
    confirmButtonText: 'Ya, Setujui',
    confirmButtonColor: '#16a34a',
  });

  if (!ok) return;

  submitApproval();
}

function rejectRequest() {
  const notes = prompt('Masukkan alasan penolakan:');
  if (!notes) return;

  router.put(`/overtime/${props.overtime.id}`, {
    status: 'rejected',
    review_notes: notes,
  });
}
</script>
