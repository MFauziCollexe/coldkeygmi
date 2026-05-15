<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mx-auto max-w-6xl space-y-4">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 class="text-2xl font-bold">Detail Purchase Requisition</h2>
            <p class="text-sm text-slate-400">Informasi detail purchase requisition.</p>
          </div>
          <Link href="/gmisl/procurement/purchase-requisition" class="text-sm text-indigo-400">Back to list</Link>
        </div>

        <div v-if="$page.props.flash?.success" class="rounded border border-green-600 bg-green-600/20 px-4 py-3 text-sm text-green-300">
          {{ $page.props.flash.success }}
        </div>

        <div v-if="$page.props.errors?.signature" class="rounded border border-rose-600 bg-rose-600/20 px-4 py-3 text-sm text-rose-200">
          {{ $page.props.errors.signature }}
        </div>

        <div
          v-if="isOwnerUser() && !purchaseRequisition.all_image_attachments_signed && hasImageAttachments"
          class="rounded border border-amber-600 bg-amber-600/10 px-4 py-3 text-sm text-amber-200"
        >
          Semua attachment gambar wajib ditandatangani dulu sebelum PR bisa di-approve.
        </div>

        <form class="space-y-4 rounded bg-slate-800 p-4 md:p-6">
          <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <div class="space-y-4">
              <div>
                <label class="mb-1 block text-sm text-slate-300">PR Number</label>
                <input :value="purchaseRequisition.pr_number || ''" disabled class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-3 text-slate-100 disabled:opacity-100" />
              </div>

              <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="relative pt-0">
                  <label class="absolute left-3 -top-0.5 z-10 -translate-y-1/2 bg-slate-800 px-1 text-sm text-slate-300">PR Date</label>
                  <EnhancedDatePicker
                    :model-value="purchaseRequisition.pr_date || ''"
                    disabled
                    placeholder="dd/mm/yyyy"
                    input-class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-3 text-slate-100 placeholder-transparent"
                  />
                </div>
                <div class="relative pt-0">
                  <label class="absolute left-3 -top-0.5 z-10 -translate-y-1/2 bg-slate-800 px-1 text-sm text-slate-300">Request Date</label>
                  <EnhancedDatePicker
                    :model-value="purchaseRequisition.request_date || ''"
                    disabled
                    placeholder="dd/mm/yyyy"
                    input-class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-3 text-slate-100 placeholder-transparent"
                  />
                </div>
              </div>

              <div>
                <label class="mb-1 block text-sm text-slate-300">Priority</label>
                <input :value="formatPriority(purchaseRequisition.priority)" disabled class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-3 text-slate-100 disabled:opacity-100" />
              </div>
            </div>

            <div class="space-y-4">
              <div>
                <label class="mb-1 block text-sm text-slate-300">Requestor</label>
                <input :value="purchaseRequisition.requester_name || '-'" disabled class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-3 text-slate-100 disabled:opacity-100" />
              </div>
              <div>
                <label class="mb-1 block text-sm text-slate-300">Department</label>
                <input :value="purchaseRequisition.department_name || '-'" disabled class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-3 text-slate-100 disabled:opacity-100" />
              </div>
            </div>
          </div>

          <div class="overflow-hidden rounded-lg border border-slate-700">
            <div class="border-b border-slate-700 bg-slate-900 px-4 py-3">
              <h3 class="font-semibold text-slate-100">Items</h3>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full table-auto">
                <thead>
                  <tr class="text-left text-slate-400">
                    <th class="py-2 pl-4">#</th>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>UoM</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, index) in purchaseRequisition.items" :key="index" class="border-t border-slate-700">
                    <td class="py-3 pl-4">{{ index + 1 }}</td>
                    <td>{{ item.product_name }}</td>
                    <td>{{ item.qty }}</td>
                    <td>{{ item.uom }}</td>
                  </tr>
                  <tr v-if="!purchaseRequisition.items?.length">
                    <td colspan="4" class="py-4 text-center text-sm text-slate-400">Tidak ada item.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="space-y-3 rounded-lg border border-slate-700 p-4">
            <div class="flex items-center justify-between">
              <label class="block text-sm font-medium text-slate-200">Attachments</label>
              <span v-if="purchaseRequisition.attachments?.length" class="text-xs text-slate-400">
                {{ purchaseRequisition.attachments.length }} file(s)
              </span>
            </div>

            <div v-if="purchaseRequisition.attachments?.length" class="space-y-2">
              <div
                v-for="attachment in purchaseRequisition.attachments"
                :key="attachment.id"
                class="flex flex-col justify-between gap-2 rounded bg-slate-900 px-3 py-3 text-sm sm:flex-row sm:items-center"
              >
                <div class="flex min-w-0 flex-1 items-center gap-3">
                  <button
                    v-if="isImageFile(attachment.filename)"
                    type="button"
                    class="block flex-shrink-0"
                    @click="openImagePreview(attachment)"
                  >
                    <img
                      :src="attachment.url"
                      class="h-12 w-12 rounded border border-slate-700 object-cover"
                      alt="preview"
                    />
                  </button>
                  <div v-else class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded bg-slate-700 text-xs text-slate-400">
                    FILE
                  </div>

                  <div class="min-w-0 flex-1">
                    <a
                      :href="attachment.url"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="block truncate text-slate-100 hover:text-indigo-300"
                    >
                      {{ attachment.filename }}
                    </a>
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-400">
                      <span>{{ formatAttachmentSize(attachment.size) }}</span>
                      <span
                        v-if="attachment.signature_status"
                        class="rounded px-1.5 py-0.5 text-[10px] font-semibold"
                        :class="{
                          'bg-emerald-700/30 text-emerald-300': attachment.signature_status === 'signed',
                          'bg-amber-700/30 text-amber-300': attachment.signature_status === 'pending',
                          'bg-rose-700/30 text-rose-300': attachment.signature_status === 'rejected',
                        }"
                      >
                        {{ formatSignatureStatus(attachment.signature_status) }}
                      </span>
                      <span v-if="attachment.signed_at" class="text-slate-500">
                        by {{ attachment.signed_by_name }} • {{ formatDate(attachment.signed_at) }}
                      </span>
                    </div>
                  </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 self-end sm:self-center">
                  <a
                    :href="attachment.original_url || attachment.url"
                    download
                    class="px-2 py-1 text-xs text-slate-400 hover:text-blue-400"
                    title="Download original"
                  >
                    Original
                  </a>

                  <button
                    v-if="canSignAttachment(attachment) && isImageFile(attachment.filename)"
                    @click="openSignatureModal(attachment)"
                    class="rounded bg-indigo-600 px-3 py-1 text-xs font-semibold text-white hover:bg-indigo-700"
                    type="button"
                  >
                    Sign
                  </button>

                  <button
                    v-if="attachment.signature_status === 'signed' && attachment.signed_url"
                    type="button"
                    class="rounded bg-emerald-600 px-3 py-1 text-xs font-semibold text-white hover:bg-emerald-700"
                    @click="openImagePreview(attachment, 'signed')"
                  >
                    View Signed
                  </button>

                  <a
                    v-if="attachment.signature_status === 'signed' && attachment.signed_url"
                    :href="attachment.signed_url"
                    :download="signedFilename(attachment)"
                    class="rounded bg-slate-700 px-3 py-1 text-xs text-white hover:bg-slate-600"
                  >
                    Download
                  </a>
                </div>
              </div>
            </div>

            <div v-else class="text-sm text-slate-400">Tidak ada attachment.</div>
          </div>

          <div>
            <label class="mb-1 block text-sm text-slate-300">Note</label>
            <textarea :value="purchaseRequisition.note || ''" rows="4" disabled class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-3 text-slate-100 disabled:opacity-100"></textarea>
          </div>

          <div v-if="purchaseRequisition.reject_note">
            <label class="mb-1 block text-sm text-slate-300">Reject Note</label>
            <textarea :value="purchaseRequisition.reject_note || ''" rows="4" disabled class="w-full rounded-lg border border-rose-700 bg-slate-800 px-3 py-3 text-slate-100 disabled:opacity-100"></textarea>
          </div>

          <div class="flex flex-col-reverse gap-3 border-t border-slate-700 pt-4 sm:flex-row sm:justify-end">
            <Link href="/gmisl/procurement/purchase-requisition" class="rounded bg-slate-700 px-4 py-2 text-center text-white hover:bg-slate-600">Close</Link>
            <button v-if="canDelete" type="button" class="rounded bg-rose-600 px-4 py-2 text-white hover:bg-rose-700" @click="confirmDelete">
              Delete
            </button>
            <button v-if="canReject" type="button" class="rounded bg-rose-600 px-4 py-2 text-white hover:bg-rose-700" @click="confirmReject">
              Reject
            </button>
            <button v-if="canApprove" type="button" class="rounded bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700" @click="confirmApprove">
              Approve
            </button>
          </div>
        </form>

        <AttachmentSignatureModal
          v-model:show="showSignatureModal"
          :attachment="selectedAttachment"
          @signed="handleSignatureSigned"
          @close="showSignatureModal = false"
        />

        <div
          v-if="showImagePreviewModal && previewAttachment"
          class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-3 sm:p-4"
          @click.self="closeImagePreview"
        >
          <div class="w-full max-w-5xl rounded-xl bg-slate-900 p-3 shadow-2xl sm:p-4">
            <div class="mb-4 flex items-center justify-between gap-3">
              <div class="min-w-0">
                <h3 class="truncate text-lg font-semibold text-white">
                  {{ previewTitle }}
                </h3>
                <div class="text-xs text-slate-400">
                  <template v-if="previewAttachment.signature_status === 'signed'">
                    {{ previewAttachment.signed_by_name || '-' }} • {{ formatDate(previewAttachment.signed_at) || '-' }}
                  </template>
                  <template v-else>
                    {{ previewAttachment.uploader_name || '-' }}
                  </template>
                </div>
              </div>
              <button
                type="button"
                class="rounded bg-slate-700 px-3 py-2 text-sm text-white hover:bg-slate-600"
                @click="closeImagePreview"
              >
                Close
              </button>
            </div>

            <div class="overflow-hidden rounded-lg border border-slate-700 bg-black">
              <img
                :src="previewUrl"
                :alt="previewAttachment.filename || 'Attachment preview'"
                class="max-h-[78vh] w-full object-contain"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EnhancedDatePicker from '@/Components/EnhancedDatePicker.vue';
import Swal from 'sweetalert2';
import AttachmentSignatureModal from '@/Components/AttachmentSignatureModal.vue';

const props = defineProps({
  purchaseRequisition: { type: Object, required: true },
  currentUser: { type: Object, default: () => ({}) },
});

const canApprove = props.purchaseRequisition.can_approve === true;
const canReject = props.purchaseRequisition.can_reject === true;
const isItUser = String(props.currentUser?.department_code || '').toUpperCase() === 'IT';
const canDelete = isItUser;
const hasImageAttachments = (props.purchaseRequisition.attachments || []).some((attachment) => attachment.is_image === true || isImageFile(attachment.filename));

const showSignatureModal = ref(false);
const selectedAttachment = ref(null);
const showImagePreviewModal = ref(false);
const previewAttachment = ref(null);
const previewUrl = ref('');
const previewTitle = ref('');

function isOwnerUser() {
  return String(props.currentUser?.department_code || '').toUpperCase() === 'OWNER';
}

function canSignAttachment(attachment) {
  const isOwner = isOwnerUser();
  const isPending = attachment.signature_status === 'pending';
  const isImage = isImageFile(attachment.filename);
  const prWaitingOrApproved = ['waiting', 'approved'].includes(
    props.purchaseRequisition.status?.toLowerCase()
  );

  return isOwner && isPending && isImage && prWaitingOrApproved;
}

function openSignatureModal(attachment) {
  selectedAttachment.value = attachment;
  showSignatureModal.value = true;
}

function openImagePreview(attachment, mode = 'current') {
  previewAttachment.value = attachment;
  previewUrl.value = mode === 'signed' && attachment.signed_url
    ? attachment.signed_url
    : (attachment.url || attachment.original_url || attachment.signed_url || '');
  previewTitle.value = mode === 'signed' && attachment.signed_url
    ? `Signed Preview: ${attachment.filename || 'Attachment'}`
    : `Image Preview: ${attachment.filename || 'Attachment'}`;
  showImagePreviewModal.value = true;
}

function closeImagePreview() {
  showImagePreviewModal.value = false;
  previewAttachment.value = null;
  previewUrl.value = '';
  previewTitle.value = '';
}

function handleSignatureSigned() {
  window.location.reload();
}

function formatPriority(priority) {
  const normalized = String(priority || '').trim().toLowerCase();
  if (normalized === 'urgent') return 'Urgent';
  if (normalized === 'low') return 'Low';
  return 'Medium';
}

function formatFileSize(bytes) {
  if (!bytes) return '0 B';
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(1024));
  return `${Math.round((bytes / Math.pow(1024, i)) * 100) / 100} ${sizes[i]}`;
}

function formatAttachmentSize(value) {
  if (typeof value === 'string') {
    return value;
  }

  return formatFileSize(value);
}

function formatSignatureStatus(status) {
  const map = {
    pending: 'Pending',
    signed: 'Signed',
    rejected: 'Rejected',
  };
  return map[status] || status;
}

function formatDate(dateStr) {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

function signedFilename(attachment) {
  const originalName = attachment.filename?.split('.').slice(0, -1).join('.') || 'document';
  return `signed_${attachment.id}_${originalName}.jpg`;
}

function isImageFile(filename) {
  if (!filename) return false;
  const ext = filename.split('.').pop()?.toLowerCase();
  return ['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(ext);
}

async function confirmApprove() {
  const result = await Swal.fire({
    title: 'Approve Purchase Requisition?',
    text: 'Are you sure you want to approve this PR?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#10b981',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Yes, Approve',
    cancelButtonText: 'No, Cancel',
  });

  if (result.isConfirmed) {
    approve();
  }
}

async function confirmReject() {
  const result = await Swal.fire({
    title: 'Reject Purchase Requisition?',
    text: 'Please enter a reason for rejection:',
    icon: 'warning',
    input: 'textarea',
    inputLabel: 'Rejection Reason',
    inputPlaceholder: 'Enter reason for rejection...',
    inputAttributes: {
      'aria-label': 'Rejection reason',
    },
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Yes, Reject',
    cancelButtonText: 'No, Cancel',
    inputValidator: (value) => {
      if (!value) {
        return 'Please enter a rejection reason!';
      }
      return null;
    },
  });

  if (result.isConfirmed) {
    reject(result.value);
  }
}

function approve() {
  router.post(`/gmisl/procurement/purchase-requisition/${props.purchaseRequisition.id}/approve`, {}, {
    preserveScroll: true,
  });
}

function reject(note) {
  router.post(`/gmisl/procurement/purchase-requisition/${props.purchaseRequisition.id}/reject`, { reject_note: note }, {
    preserveScroll: true,
  });
}

async function confirmDelete() {
  const result = await Swal.fire({
    title: 'Delete Purchase Requisition?',
    text: 'Are you sure you want to delete this PR? This action cannot be undone.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Yes, Delete',
    cancelButtonText: 'No, Cancel',
  });

  if (result.isConfirmed) {
    destroy();
  }
}

function destroy() {
  router.delete(`/gmisl/procurement/purchase-requisition/${props.purchaseRequisition.id}`, {}, {
    preserveScroll: true,
  });
}
</script>
