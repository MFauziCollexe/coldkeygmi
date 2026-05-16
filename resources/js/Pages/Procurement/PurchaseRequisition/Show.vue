<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mx-auto max-w-7xl space-y-4">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 class="text-2xl font-bold">Detail Purchase Requisition</h2>
            <p class="text-sm text-slate-400">Informasi header PR dan detail item per baris.</p>
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

        <div class="space-y-4 rounded bg-slate-800 p-4 md:p-6">
          <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <div class="space-y-4">
              <ReadOnlyField label="No PR" :value="purchaseRequisition.pr_number || '-'" />
              <ReadOnlyField label="PR Date" :value="purchaseRequisition.pr_date || '-'" />
              <ReadOnlyField label="Priority" :value="formatPriority(purchaseRequisition.priority)" />
            </div>

            <div class="space-y-4">
              <ReadOnlyField label="Requestor" :value="purchaseRequisition.requester_name || '-'" />
              <ReadOnlyField label="Department" :value="purchaseRequisition.department_name || '-'" />
              <ReadOnlyField label="Status" :value="formatStatus(purchaseRequisition.status)" />
            </div>
          </div>

          <div class="overflow-hidden rounded-lg border border-slate-600 bg-[#d9dde8]">
            <div class="border-b border-slate-500 bg-gradient-to-b from-[#7286b8] to-[#506898] px-4 py-2.5">
              <h3 class="text-sm font-semibold uppercase tracking-wide text-white">PR Item Detail</h3>
            </div>

            <div class="overflow-x-auto p-1">
              <table class="w-full min-w-[1200px] border-collapse text-sm text-slate-800">
                <thead>
                  <tr class="bg-gradient-to-b from-[#7489ba] to-[#556d9a] text-center text-[12px] font-semibold text-white">
                    <th class="border border-slate-400 px-2 py-1.5">Item</th>
                    <th class="border border-slate-400 px-2 py-1.5">Description</th>
                    <th class="border border-slate-400 px-2 py-1.5">Qty</th>
                    <th class="border border-slate-400 px-2 py-1.5">Item Unit</th>
                    <th class="border border-slate-400 px-2 py-1.5">Required Date</th>
                    <th class="border border-slate-400 px-2 py-1.5">Notes</th>
                    <th class="border border-slate-400 px-2 py-1.5">Qty Received</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(item, index) in purchaseRequisition.items"
                    :key="item.id || index"
                    class="align-top bg-[#f7f7f9]"
                  >
                    <td class="border border-slate-300 px-2 py-1.5 font-medium">
                      {{ item.item_code || item.item_name || '-' }}
                    </td>
                    <td class="border border-slate-300 px-2 py-1.5">
                      <div>{{ item.description_of_goods || item.item_name || '-' }}</div>
                    </td>
                    <td class="border border-slate-300 px-2 py-1.5 text-center">
                      {{ item.quantity || 0 }}
                    </td>
                    <td class="border border-slate-300 px-2 py-1.5">
                      {{ item.unit || '-' }}
                    </td>
                    <td class="border border-slate-300 px-2 py-1.5 text-center">
                      {{ formatCompactDate(item.required_date) }}
                    </td>
                    <td class="border border-slate-300 px-2 py-1.5">
                      {{ item.specification || '-' }}
                    </td>
                    <td class="border border-slate-300 bg-[#e7e7ea] px-2 py-1.5 text-center">
                      0
                    </td>
                  </tr>
                  <tr v-if="!purchaseRequisition.items?.length">
                    <td colspan="7" class="border border-slate-300 py-4 text-center text-sm text-slate-500">Tidak ada item.</td>
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
                    <a :href="attachment.url" target="_blank" rel="noopener noreferrer" class="block truncate text-slate-100 hover:text-indigo-300">
                      {{ attachment.filename }}
                    </a>
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-400">
                      <span>{{ attachment.size }}</span>
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
                        by {{ attachment.signed_by_name }} - {{ formatDate(attachment.signed_at) }}
                      </span>
                    </div>
                  </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 self-end sm:self-center">
                  <a :href="attachment.original_url || attachment.url" download class="px-2 py-1 text-xs text-slate-400 hover:text-blue-400">
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
        </div>

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
                <h3 class="truncate text-lg font-semibold text-white">{{ previewTitle }}</h3>
                <div class="text-xs text-slate-400">
                  <template v-if="previewAttachment.signature_status === 'signed'">
                    {{ previewAttachment.signed_by_name || '-' }} - {{ formatDate(previewAttachment.signed_at) || '-' }}
                  </template>
                  <template v-else>
                    {{ purchaseRequisition.pr_number || '-' }}
                  </template>
                </div>
              </div>
              <button type="button" class="rounded bg-slate-700 px-3 py-2 text-sm text-white hover:bg-slate-600" @click="closeImagePreview">
                Close
              </button>
            </div>

            <div class="overflow-hidden rounded-lg border border-slate-700 bg-black">
              <img :src="previewUrl" :alt="previewAttachment.filename || 'Attachment preview'" class="max-h-[78vh] w-full object-contain" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { defineComponent, h, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
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

const ReadOnlyField = defineComponent({
  props: { label: String, value: String },
  setup(readonlyProps) {
    return () => h('div', [
      h('label', { class: 'mb-1 block text-sm text-slate-300' }, readonlyProps.label || ''),
      h('input', { value: readonlyProps.value || '', disabled: true, class: 'w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-3 text-slate-100 disabled:opacity-100' }),
    ]);
  },
});

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

function formatStatus(status) {
  const normalized = String(status || '').trim().toLowerCase();
  if (normalized === 'approved') return 'Approved';
  if (normalized === 'waiting') return 'Waiting';
  if (normalized === 'process') return 'Process';
  if (normalized === 'done') return 'Done';
  if (normalized === 'rejected') return 'Rejected';
  return normalized || '-';
}

function formatCurrency(value) {
  const number = Number(value || 0);
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 2 }).format(number);
}

function formatCompactDate(value) {
  if (!value) return '-';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  const day = String(date.getDate()).padStart(2, '0');
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const year = date.getFullYear();
  return `${day}/${month}/${year}`;
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
    router.post(`/gmisl/procurement/purchase-requisition/${props.purchaseRequisition.id}/approve`, {}, { preserveScroll: true });
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
    router.post(`/gmisl/procurement/purchase-requisition/${props.purchaseRequisition.id}/reject`, { reject_note: result.value }, { preserveScroll: true });
  }
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
    router.delete(`/gmisl/procurement/purchase-requisition/${props.purchaseRequisition.id}`, {}, { preserveScroll: true });
  }
}
</script>
