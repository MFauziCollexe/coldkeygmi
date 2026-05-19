<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mx-auto max-w-7xl space-y-4">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 class="text-2xl font-bold">Detail Purchase Requisition</h2>
            <p class="text-sm text-slate-400">Informasi header PR dan detail item per baris.</p>
          </div>
          <Link :href="backUrl" class="text-sm text-indigo-400">Back to list</Link>
        </div>

<div v-if="$page.props.flash?.success" class="rounded border border-green-600 bg-green-600/20 px-4 py-3 text-sm text-green-300">
           {{ $page.props.flash.success }}
         </div>

         <div v-if="$page.props.errors?.vendor" class="rounded border border-rose-600 bg-rose-600/20 px-4 py-3 text-sm text-rose-200">
           {{ $page.props.errors.vendor }}
         </div>

         <div v-if="$page.props.errors?.status" class="rounded border border-rose-600 bg-rose-600/20 px-4 py-3 text-sm text-rose-200">
           {{ $page.props.errors.status }}
         </div>

         <div v-if="$page.props.errors?.invoice_file" class="rounded border border-rose-600 bg-rose-600/20 px-4 py-3 text-sm text-rose-200">
           {{ $page.props.errors.invoice_file }}
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
                    <th class="border border-slate-400 px-2 py-1.5">Description</th>
                    <th class="border border-slate-400 px-2 py-1.5">Item Unit</th>
                    <th class="border border-slate-400 px-2 py-1.5">Qty</th>
                    <th class="border border-slate-400 px-2 py-1.5">Required Date</th>
                    <th class="border border-slate-400 px-2 py-1.5">Note</th>
                    <th class="border border-slate-400 px-2 py-1.5">Qty Received</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(item, index) in purchaseRequisition.items"
                    :key="item.id || index"
                    class="align-top bg-[#f7f7f9]"
                  >
                    <td class="border border-slate-300 px-2 py-1.5">
                      <div>{{ item.description_of_goods || '-' }}</div>
                    </td>
                    <td class="border border-slate-300 px-2 py-1.5">
                      {{ item.unit || '-' }}
                    </td>
                    <td class="border border-slate-300 px-2 py-1.5 text-center">
                      {{ formatQuantity(item.quantity) }}
                    </td>
                    <td class="border border-slate-300 px-2 py-1.5 text-center">
                      {{ formatCompactDate(item.required_date) }}
                    </td>
<td class="border border-slate-300 px-2 py-1.5">
                       {{ purchaseRequisition.note || '-' }}
                     </td>
                    <td class="border border-slate-300 bg-[#e7e7ea] px-2 py-1.5 text-center">
                      0
                    </td>
                  </tr>
                  <tr v-if="!purchaseRequisition.items?.length">
                    <td colspan="6" class="border border-slate-300 py-4 text-center text-sm text-slate-500">Tidak ada item.</td>
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
                    </div>
                  </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 self-end sm:self-center">
                  <a :href="attachment.original_url || attachment.url" download class="px-2 py-1 text-xs text-slate-400 hover:text-blue-400">
                    Original
                  </a>
                </div>
              </div>
            </div>

            <div v-else class="text-sm text-slate-400">Tidak ada attachment.</div>
          </div>

          <div v-if="isFatUser || purchaseRequisition.suppliers?.length" class="space-y-4 rounded-lg border border-slate-700 p-4">
            <div class="flex items-center justify-between">
              <label class="block text-sm font-medium text-slate-200">Vendor</label>
              <div class="flex items-center gap-2">
                <span class="text-xs text-slate-400">Maksimal 3 vendor</span>
                <button
                  v-if="canFatManageComparison"
                  type="button"
                  @click="openSupplierModal"
                  class="rounded bg-indigo-600 px-3 py-1 text-xs font-semibold text-white hover:bg-indigo-700"
                >
                  Tambah Vendor
                </button>
              </div>
            </div>

            <div v-if="!purchaseRequisition.suppliers?.length" class="text-sm text-slate-400">Belum ada vendor yang dipilih.</div>

            <div v-if="supplierComparisons.length" class="overflow-hidden rounded-lg border border-slate-600 bg-[#d9dde8]">
              <div class="flex items-center justify-between gap-3 border-b border-slate-500 bg-gradient-to-b from-[#7286b8] to-[#506898] px-4 py-2.5">
                <div>
                  <h3 class="text-sm font-semibold uppercase tracking-wide text-white">Vendor Comparison</h3>
                  <p class="text-xs text-blue-100/80">Vendor/supplier, qty, harga, payment type, total, dan action.</p>
                </div>
              </div>

              <div class="overflow-x-auto p-1">
                <table class="w-full min-w-[1120px] border-collapse text-sm text-slate-800">
<thead>
                     <tr class="bg-gradient-to-b from-[#7489ba] to-[#556d9a] text-center text-[12px] font-semibold text-white">
                       <th class="w-[22%] border border-slate-400 px-2 py-1.5">Vendor / Supplier</th>
                       <th class="w-[17%] border border-slate-400 px-2 py-1.5">Item</th>
                       <th class="w-[6%] border border-slate-400 px-2 py-1.5">Qty</th>
                       <th class="w-[14%] border border-slate-400 px-2 py-1.5">Harga</th>
                       <th class="w-[18%] border border-slate-400 px-2 py-1.5">Payment Type</th>
                       <th class="w-[11%] border border-slate-400 px-2 py-1.5">Total</th>
                       <th class="w-[12%] border border-slate-400 px-2 py-1.5">Action</th>
                     </tr>
                   </thead>
                  <tbody>
                    <template
                      v-for="comparison in supplierComparisons"
                      :key="`vendor-group-${comparison.supplier_id}`"
                    >
                      <tr
                        v-for="(item, index) in purchaseRequisition.items"
                        :key="`vendor-row-${comparison.supplier_id}-${item.id}`"
                        class="align-top bg-[#f7f7f9]"
                      >
                        <td
                          v-if="index === 0"
                          :rowspan="purchaseRequisition.items.length"
                          class="border border-slate-300 px-2 py-2"
                        >
                          <div class="font-medium">{{ comparison.name || '-' }}</div>
                          <div v-if="comparison.supplier_type" class="text-xs text-slate-500">{{ comparison.supplier_type }}</div>
                          <div class="text-xs text-slate-500">{{ comparison.code ? comparison.code + ' - ' : '' }}{{ comparison.contact_person || '-' }}</div>
                          <button
                            v-if="canFatManageComparison"
                            type="button"
                            class="mt-2 rounded bg-rose-600 px-2 py-1 text-[11px] font-semibold text-white hover:bg-rose-700"
                            @click="removeSupplier(comparison.supplier_id)"
                          >
                            Hapus Vendor
                          </button>
                        </td>
                        <td class="border border-slate-300 px-2 py-2">
                          <div class="font-medium">{{ item.item_name || item.item_code || '-' }}</div>
                          <div class="text-xs text-slate-500">{{ item.description_of_goods || '-' }}</div>
                        </td>
                        <td class="border border-slate-300 px-2 py-2 text-right">
                          {{ formatQuantity(item.quantity) }}
                        </td>
                        <td class="border border-slate-300 px-2 py-2">
                          <input
                            v-if="canFatManageComparison"
                            v-model="comparison.prices[item.id].quoted_price"
                            type="number"
                            min="0"
                            step="1"
                            class="w-full max-w-[140px] rounded border border-slate-300 bg-white px-2 py-1.5 text-right text-sm text-slate-800"
                            placeholder="Harga"
                          >
                          <div v-else class="w-full max-w-[140px] rounded border border-slate-200 bg-white px-2 py-1.5 text-right">
                            {{ displayQuotedPrice(comparison.prices[item.id]?.quoted_price) }}
                          </div>
                        </td>
                        <td
                          v-if="index === 0"
                          :rowspan="purchaseRequisition.items.length"
                          class="border border-slate-300 px-2 py-2"
                        >
                          <select
                            v-if="canFatManageComparison"
                            v-model="comparison.payment_terms"
                            class="w-full rounded border border-slate-300 bg-white px-2 py-1.5 text-sm text-slate-800"
                          >
                            <option value="">Pilih pembayaran</option>
                            <option
                              v-for="paymentMethod in paymentMethodOptions"
                              :key="paymentMethod"
                              :value="paymentMethod"
                            >
                              {{ paymentMethod }}
                            </option>
                          </select>
                          <div v-else class="rounded border border-slate-200 bg-white px-2 py-1.5">
                            {{ comparison.payment_terms || '-' }}
                          </div>
                        </td>
                        <td class="border border-slate-300 px-2 py-2 text-right font-semibold">
                          {{ formatCurrency(itemLineTotal(item.quantity, comparison.prices[item.id]?.quoted_price)) }}
                        </td>
                        <td class="border border-slate-300 px-2 py-2 text-center">
                          <button
                            v-if="canOwnerSelectVendor"
                            type="button"
                            class="rounded border px-3 py-1.5 text-xs font-semibold transition"
                            :class="isItemSelected(comparison, item.id)
                              ? 'border-emerald-500 bg-emerald-100 text-emerald-700'
                              : 'border-slate-400 bg-white text-slate-700 hover:border-slate-500 hover:bg-slate-50'"
                            @click="toggleItemSelection(comparison.supplier_id, item.id)"
                          >
                            {{ isItemSelected(comparison, item.id) ? 'Selected' : 'Select' }}
                          </button>
                          <span
                            v-else-if="isItemSelected(comparison, item.id)"
                            class="inline-flex rounded border border-emerald-500 bg-emerald-100 px-3 py-1.5 text-xs font-semibold text-emerald-700"
                          >
                            Selected
                          </span>
                        </td>
                      </tr>
                    </template>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div v-if="canViewInvoiceSection" class="space-y-4 rounded-lg border border-slate-700 p-4">
            <div class="flex items-center justify-between gap-3">
              <div>
                <label class="block text-sm font-medium text-slate-200">Invoice Vendor</label>
                <p class="mt-1 text-xs text-slate-400">Area upload invoice hanya untuk departemen FAT setelah status On Process by Vendor.</p>
              </div>
              <div v-if="purchaseRequisition.po_processed_at" class="text-xs text-slate-400">
                Processed: {{ purchaseRequisition.po_processed_at }}
              </div>
            </div>

            <div v-if="purchaseRequisition.po_invoice_url" class="rounded-lg bg-slate-900 px-3 py-3 text-sm">
              <div class="text-slate-300">File invoice saat ini:</div>
              <a :href="purchaseRequisition.po_invoice_url" target="_blank" rel="noopener noreferrer" class="mt-1 block truncate text-indigo-300 hover:text-indigo-200">
                {{ purchaseRequisition.po_invoice_filename || 'Lihat Invoice' }}
              </a>
            </div>

            <div v-if="canUploadInvoice" class="space-y-3">
              <input
                type="file"
                accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx"
                class="block w-full rounded border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-200"
                @change="handleInvoiceUpload"
              />
              <div v-if="invoiceForm.invoice_file" class="rounded bg-slate-900 px-3 py-2 text-sm text-slate-300">
                {{ invoiceForm.invoice_file.name }}
              </div>
              <div class="flex justify-end">
                <button
                  type="button"
                  class="rounded bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700 disabled:opacity-50"
                  :disabled="invoiceForm.processing || !invoiceForm.invoice_file"
                  @click="submitInvoice"
                >
                  {{ invoiceForm.processing ? 'Uploading...' : 'Upload Invoice' }}
                </button>
              </div>
            </div>
          </div>

          <div v-if="showSupplierModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-3 sm:p-4">
            <div class="w-full max-w-lg rounded-xl bg-slate-900 p-4 shadow-2xl">
              <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-white">Pilih Vendor</h3>
                <button type="button" class="rounded bg-slate-700 px-3 py-2 text-sm text-white hover:bg-slate-600" @click="closeSupplierModal">
                  Tutup
                </button>
              </div>
              <div class="max-h-96 space-y-2 overflow-y-auto">
                <label
                  v-for="supplier in allSuppliers"
                  :key="supplier.id"
                  class="flex items-center gap-3 rounded bg-slate-800 p-3 hover:bg-slate-700"
                >
                  <input
                    type="checkbox"
                    :value="supplier.id"
                    v-model="selectedSupplierIds"
                    :disabled="selectedSupplierIds.length >= 3 && !selectedSupplierIds.includes(supplier.id)"
                    class="h-4 w-4 rounded border-slate-600 bg-slate-700 text-indigo-600"
                  >
                  <div class="flex-1">
                    <div class="font-medium text-slate-100">{{ supplier.name }}</div>
                    <div v-if="supplier.supplier_type" class="text-xs text-slate-400">{{ supplier.supplier_type }}</div>
                    <div class="text-xs text-slate-500">{{ supplier.code ? supplier.code + ' - ' : '' }}{{ supplier.contact_person || '-' }}</div>
                  </div>
                </label>
              </div>
              <div class="mt-4 flex justify-end gap-2 border-t border-slate-700 pt-4">
                <button type="button" class="rounded bg-slate-700 px-4 py-2 text-white hover:bg-slate-600" @click="closeSupplierModal">
                  Batal
                </button>
                <button
                  type="button"
                  @click="saveSuppliers"
                  :disabled="selectedSupplierIds.length === 0"
                  class="rounded bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700 disabled:opacity-50"
                >
                  Simpan ({{ selectedSupplierIds.length }}/3)
                </button>
              </div>
            </div>
          </div>

          <div class="rounded-lg border border-slate-700 p-4">
            <label class="mb-1 block text-sm text-slate-300">Note</label>
            <div class="whitespace-pre-wrap rounded-lg border border-slate-700 bg-slate-800 px-3 py-3 text-slate-100">
              {{ purchaseRequisition.note || '-' }}
            </div>
          </div>

          <div v-if="purchaseRequisition.reject_note" class="rounded-lg border border-rose-700 p-4">
            <label class="mb-1 block text-sm text-slate-300">Reject Note</label>
            <div class="whitespace-pre-wrap rounded-lg border border-rose-700 bg-slate-800 px-3 py-3 text-slate-100">
              {{ purchaseRequisition.reject_note || '-' }}
            </div>
          </div>

          <div class="flex flex-col-reverse gap-3 border-t border-slate-700 pt-4 sm:flex-row sm:justify-end">
            <Link :href="backUrl" class="rounded bg-slate-700 px-4 py-2 text-center text-white hover:bg-slate-600">Close</Link>
            <button
              v-if="canSubmitVendorRequest"
              type="button"
              class="rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700"
              @click="saveSupplierComparisons"
            >
              Ajukan Permintaan
            </button>
            <button
              v-if="canProcessVendor"
              type="button"
              class="rounded bg-amber-600 px-4 py-2 text-white hover:bg-amber-700"
              @click="confirmProcessVendor"
            >
              On Process by Vendor
            </button>
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

        <div
          v-if="showImagePreviewModal && previewAttachment"
          class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-3 sm:p-4"
          @click.self="closeImagePreview"
        >
          <div class="w-full max-w-5xl rounded-xl bg-slate-900 p-3 shadow-2xl sm:p-4">
            <div class="mb-4 flex items-center justify-between gap-3">
              <div class="min-w-0">
                <h3 class="truncate text-lg font-semibold text-white">{{ previewTitle }}</h3>
                <div class="text-xs text-slate-400">{{ purchaseRequisition.pr_number || '-' }}</div>
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
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
  purchaseRequisition: { type: Object, required: true },
  currentUser: { type: Object, default: () => ({}) },
  allSuppliers: { type: Array, default: () => [] },
  paymentMethodOptions: { type: Array, default: () => [] },
  backUrl: { type: String, default: '/gmisl/procurement/purchase-requisition' },
});

const canApprove = props.purchaseRequisition.can_approve === true;
const canReject = props.purchaseRequisition.can_reject === true;
const canProcessVendor = props.purchaseRequisition.can_process_vendor === true;
const canUploadInvoice = props.purchaseRequisition.can_upload_invoice === true;
const isItUser = String(props.currentUser?.department_code || '').toUpperCase() === 'IT';
const isFatUser = String(props.currentUser?.department_code || '').toUpperCase() === 'FAT';
const isOwnerDepartmentUser = String(props.currentUser?.department_code || '').toUpperCase() === 'OWNER';
const currentStatus = String(props.purchaseRequisition?.status || '').trim().toLowerCase();
const isWaitingStatus = currentStatus === 'waiting';
const isApprovedStatus = currentStatus === 'approved';
const isProcessStatus = currentStatus === 'process';
const canFatManageComparison = isFatUser && isWaitingStatus;
const canOwnerSelectVendor = isOwnerDepartmentUser && isWaitingStatus;
const canSubmitVendorRequest = isFatUser && isWaitingStatus;
const canViewInvoiceSection = isFatUser && (isProcessStatus || canUploadInvoice || Boolean(props.purchaseRequisition.po_invoice_url));
const canDelete = props.purchaseRequisition.can_delete === true || isItUser;
const showImagePreviewModal = ref(false);
const previewAttachment = ref(null);
const previewUrl = ref('');
const previewTitle = ref('');
const showSupplierModal = ref(false);
const selectedSupplierIds = ref([]);
const supplierComparisons = ref(buildSupplierComparisons(props.purchaseRequisition));
const invoiceForm = useForm({
  invoice_file: null,
});

const ReadOnlyField = defineComponent({
  props: { label: String, value: String },
  setup(readonlyProps) {
    return () => h('div', [
      h('label', { class: 'mb-1 block text-sm text-slate-300' }, readonlyProps.label || ''),
      h('input', { value: readonlyProps.value || '', disabled: true, class: 'w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-3 text-slate-100 disabled:opacity-100' }),
    ]);
  },
});

function openImagePreview(attachment, mode = 'current') {
  previewAttachment.value = attachment;
  previewUrl.value = attachment.url || attachment.original_url || attachment.signed_url || '';
  previewTitle.value = `Image Preview: ${attachment.filename || 'Attachment'}`;
  showImagePreviewModal.value = true;
}

function closeImagePreview() {
  showImagePreviewModal.value = false;
  previewAttachment.value = null;
  previewUrl.value = '';
  previewTitle.value = '';
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

function buildSupplierComparisons(purchaseRequisition) {
  const items = purchaseRequisition?.items || [];
  const comparisons = purchaseRequisition?.supplier_comparisons || [];

  return comparisons.map((comparison) => {
    const prices = {};

    items.forEach((item) => {
      const matchedItem = (comparison.items || []).find((entry) => Number(entry.purchase_requisition_item_id) === Number(item.id));
      prices[item.id] = {
        quoted_price: matchedItem?.quoted_price ?? '',
        is_selected: matchedItem?.is_selected === true,
      };
    });

    return {
      supplier_id: comparison.supplier_id,
      name: comparison.name,
      supplier_type: comparison.supplier_type,
      code: comparison.code,
      contact_person: comparison.contact_person,
      phone: comparison.phone,
      email: comparison.email,
      lead_time: comparison.lead_time || '',
      payment_terms: comparison.payment_terms || '',
      prices,
    };
  });
}

function displayQuotedPrice(value) {
  if (value === null || value === undefined || value === '') return '-';
  return formatCurrency(value);
}

function formatQuantity(value) {
  const number = Number(value || 0);
  if (!Number.isFinite(number)) return '-';
  return String(Math.round(number));
}

function itemLabel(item) {
  return item.item_code || item.item_name || '-';
}

function supplierTotal(comparison) {
  return (props.purchaseRequisition.items || []).reduce((total, item) => {
    return total + itemLineTotal(item.quantity, comparison.prices[item.id]?.quoted_price);
  }, 0);
}

function itemLineTotal(quantity, unitPrice) {
  const qty = Number(quantity || 0);
  const price = Number(unitPrice || 0);

  if (!Number.isFinite(qty) || !Number.isFinite(price)) {
    return 0;
  }

  return qty * price;
}

function isItemSelected(comparison, itemId) {
  return comparison.prices[itemId]?.is_selected === true;
}

function hasSelectedVendorForAllItems() {
  const items = props.purchaseRequisition.items || [];

  if (!items.length) {
    return false;
  }

  return items.every((item) => {
    return supplierComparisons.value.some((comparison) => comparison.prices[item.id]?.is_selected === true);
  });
}

function toggleItemSelection(supplierId, itemId) {
  supplierComparisons.value = supplierComparisons.value.map((comparison) => {
    const nextPrices = { ...comparison.prices };
    const currentItem = nextPrices[itemId] || { quoted_price: '', is_selected: false };

    nextPrices[itemId] = {
      ...currentItem,
      is_selected: comparison.supplier_id === supplierId,
    };

    return {
      ...comparison,
      prices: nextPrices,
    };
  });
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
  if (!hasSelectedVendorForAllItems()) {
    await Swal.fire({
      title: 'Vendor belum dipilih',
      text: 'Pilih satu vendor untuk setiap item sebelum approve PR.',
      icon: 'warning',
      confirmButtonColor: '#f59e0b',
      confirmButtonText: 'OK',
    });
    return;
  }

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
    submitSupplierComparisons(() => {
      router.post(`/gmisl/procurement/purchase-requisition/${props.purchaseRequisition.id}/approve`, {}, { preserveScroll: true });
    });
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

async function confirmProcessVendor() {
  const result = await Swal.fire({
    title: 'Ubah ke On Process by Vendor?',
    text: 'Status PR akan berubah menjadi On Process by Vendor.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#d97706',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Ya, Proses',
    cancelButtonText: 'Batal',
  });

  if (result.isConfirmed) {
    router.post(`/gmisl/procurement/purchase-requisition/${props.purchaseRequisition.id}/process-vendor`, {}, { preserveScroll: true });
  }
}

function saveSuppliers() {
  if (selectedSupplierIds.value.length === 0) return;
  
  router.post(`/gmisl/procurement/purchase-requisition/${props.purchaseRequisition.id}/suppliers`, {
    supplier_ids: selectedSupplierIds.value,
  }, {
    onSuccess: () => {
      window.location.reload();
    },
    onError: () => {
      showSupplierModal.value = true;
    },
  });
}

function handleInvoiceUpload(event) {
  const file = event?.target?.files?.[0] || null;
  invoiceForm.invoice_file = file;
}

function submitInvoice() {
  if (!invoiceForm.invoice_file) {
    Swal.fire({
      title: 'File invoice belum dipilih',
      text: 'Pilih file invoice terlebih dahulu sebelum upload.',
      icon: 'warning',
      confirmButtonColor: '#f59e0b',
      confirmButtonText: 'OK',
    });
    return;
  }

  invoiceForm.post(`/gmisl/procurement/purchase-requisition/${props.purchaseRequisition.id}/invoice`, {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      invoiceForm.reset();
    },
  });
}

function saveSupplierComparisons() {
  if (!supplierComparisons.value.length) return;

  if (!validateSupplierComparisons()) {
    return;
  }

  submitSupplierComparisons(() => {
    window.location.reload();
  });
}

function validateSupplierComparisons() {
  const hasMissingPaymentTerms = supplierComparisons.value.some((comparison) => {
    return String(comparison.payment_terms || '').trim() === '';
  });

  const hasMissingQuotedPrice = supplierComparisons.value.some((comparison) => {
    return (props.purchaseRequisition.items || []).some((item) => {
      const rawValue = comparison.prices[item.id]?.quoted_price;
      return rawValue === '' || rawValue === null || rawValue === undefined;
    });
  });

  if (!hasMissingPaymentTerms && !hasMissingQuotedPrice) {
    return true;
  }

  Swal.fire({
    title: 'Data vendor belum lengkap',
    text: 'Harga dan payment type untuk semua vendor harus diisi sebelum ajukan permintaan.',
    icon: 'warning',
    confirmButtonColor: '#f59e0b',
    confirmButtonText: 'OK',
  });

  return false;
}

function submitSupplierComparisons(onSuccess) {
  router.post(`/gmisl/procurement/purchase-requisition/${props.purchaseRequisition.id}/supplier-comparisons`, {
    comparisons: supplierComparisons.value.map((comparison) => ({
      supplier_id: comparison.supplier_id,
      lead_time: comparison.lead_time || null,
      payment_terms: comparison.payment_terms || null,
      items: (props.purchaseRequisition.items || []).map((item) => {
        const priceEntry = comparison.prices[item.id] || {};
        const rawValue = priceEntry.quoted_price;

        return {
          purchase_requisition_item_id: item.id,
          quoted_price: rawValue === '' || rawValue === null || rawValue === undefined ? null : Number(rawValue),
          is_selected: priceEntry.is_selected === true,
        };
      }),
    })),
  }, {
    onSuccess: () => {
      if (typeof onSuccess === 'function') {
        onSuccess();
      }
    },
  });
}

async function removeSupplier(supplierId) {
  const result = await Swal.fire({
    title: 'Hapus vendor?',
    text: 'Vendor ini akan dikeluarkan dari komparasi PR.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal',
  });

  if (!result.isConfirmed) return;

  const remainingSupplierIds = (props.purchaseRequisition.suppliers || [])
    .map((supplier) => Number(supplier.id))
    .filter((id) => id !== Number(supplierId));

  router.post(`/gmisl/procurement/purchase-requisition/${props.purchaseRequisition.id}/suppliers`, {
    supplier_ids: remainingSupplierIds,
  }, {
    onSuccess: () => {
      window.location.reload();
    },
  });
}

function openSupplierModal() {
  selectedSupplierIds.value = (props.purchaseRequisition.suppliers || []).map(s => s.id);
  showSupplierModal.value = true;
}

function closeSupplierModal() {
  showSupplierModal.value = false;
  selectedSupplierIds.value = [];
}
</script>
