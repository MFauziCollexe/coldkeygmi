<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mx-auto max-w-6xl space-y-4">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 class="text-2xl font-bold">Purchase Order Form</h2>
            <p class="text-sm text-slate-400">Halaman form terpisah untuk proses dan update purchase order.</p>
          </div>
          <Link href="/gmisl/procurement/purchase-order" class="text-sm text-indigo-400">Back to list</Link>
        </div>

        <div v-if="$page.props.flash?.success" class="rounded border border-green-600 bg-green-600/20 px-4 py-3 text-sm text-green-300">
          {{ $page.props.flash.success }}
        </div>

        <div v-if="validationErrors.length" class="rounded border border-rose-600 bg-rose-600/20 px-4 py-3 text-sm text-rose-200">
          <div v-for="(message, index) in validationErrors" :key="`po-error-${index}`">
            {{ message }}
          </div>
        </div>

        <section class="space-y-4 rounded bg-slate-800 p-4 md:p-6">
          <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div>
              <div class="text-lg font-semibold text-slate-100">{{ purchaseOrder.po_number || '-' }}</div>
              <div class="mt-1 text-sm text-slate-400">PR: {{ purchaseOrder.pr_number }}</div>
              <div class="mt-1 text-sm text-slate-400">
                {{ purchaseOrder.department_name || '-' }} | {{ purchaseOrder.requester_name || '-' }}
              </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
              <span class="rounded px-2 py-1 text-xs font-semibold" :class="priorityClass(purchaseOrder.priority)">
                {{ formatPriority(purchaseOrder.priority) }}
              </span>
              <span class="rounded px-2 py-1 text-xs font-semibold" :class="statusClass(purchaseOrder.status)">
                {{ formatStatus(purchaseOrder.status) }}
              </span>
            </div>
          </div>

          <div class="grid grid-cols-1 gap-3 text-sm text-slate-300 md:grid-cols-4">
            <div>
              <div class="text-xs uppercase tracking-wide text-slate-500">PO Number</div>
              <div>{{ purchaseOrder.po_number || '-' }}</div>
            </div>
            <div>
              <div class="text-xs uppercase tracking-wide text-slate-500">PR Date</div>
              <div>{{ purchaseOrder.pr_date || '-' }}</div>
            </div>
            <div>
              <div class="text-xs uppercase tracking-wide text-slate-500">Request Date</div>
              <div>{{ purchaseOrder.request_date || '-' }}</div>
            </div>
            <div>
              <div class="text-xs uppercase tracking-wide text-slate-500">Approved At</div>
              <div>{{ purchaseOrder.approved_at || '-' }}</div>
            </div>
            <div>
              <div class="text-xs uppercase tracking-wide text-slate-500">Approved By</div>
              <div>{{ purchaseOrder.approved_by_name || '-' }}</div>
            </div>
          </div>

          <div class="grid grid-cols-1 gap-3 text-sm text-slate-300 md:grid-cols-2">
            <div>
              <div class="text-xs uppercase tracking-wide text-slate-500">Processed At</div>
              <div>{{ purchaseOrder.po_processed_at || '-' }}</div>
            </div>
            <div>
              <div class="text-xs uppercase tracking-wide text-slate-500">Done At</div>
              <div>{{ purchaseOrder.po_done_at || '-' }}</div>
            </div>
          </div>

          <div v-if="purchaseOrder.note" class="rounded border border-slate-700 bg-slate-950/40 p-3 text-sm text-slate-300">
            <div class="mb-1 text-xs uppercase tracking-wide text-slate-500">Note</div>
            {{ purchaseOrder.note }}
          </div>

          <div v-if="purchaseOrder.po_summary?.suppliers?.length" class="space-y-2">
            <div class="text-sm font-semibold text-slate-100">Selected Vendor Summary</div>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
              <div
                v-for="supplier in purchaseOrder.po_summary.suppliers"
                :key="`supplier-summary-${supplier.supplier_id}`"
                class="rounded border border-slate-700 bg-slate-950/40 p-3 text-sm text-slate-300"
              >
                <div class="font-semibold text-slate-100">{{ supplier.supplier_name || '-' }}</div>
                <div class="mt-1 text-xs text-slate-400">{{ supplier.supplier_code || '-' }}</div>
                <div class="mt-2">Payment Type: {{ supplier.payment_terms || '-' }}</div>
                <div class="mt-1">Subtotal: {{ formatCurrency(supplier.total_amount) }}</div>
              </div>
            </div>
          </div>

          <div>
            <div class="mb-2 text-sm font-semibold text-slate-100">Approved Items</div>
            <div class="overflow-hidden rounded border border-slate-700">
              <table class="w-full table-auto text-sm">
                <thead class="bg-slate-950/50 text-left text-slate-400">
                  <tr>
                    <th class="px-3 py-2">Product</th>
                    <th class="px-3 py-2">Qty</th>
                    <th class="px-3 py-2">UoM</th>
                    <th class="px-3 py-2">Vendor</th>
                    <th class="px-3 py-2">Payment Type</th>
                    <th class="px-3 py-2 text-right">Harga Approved</th>
                    <th class="px-3 py-2 text-right">Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in approvedItems" :key="item.id" class="border-t border-slate-700 text-slate-200">
                    <td class="px-3 py-2">{{ item.item_name || item.product_name }}</td>
                    <td class="px-3 py-2">{{ item.quantity }}</td>
                    <td class="px-3 py-2">{{ item.unit }}</td>
                    <td class="px-3 py-2">{{ item.supplier_name || '-' }}</td>
                    <td class="px-3 py-2">{{ item.payment_terms || '-' }}</td>
                    <td class="px-3 py-2 text-right">{{ formatCurrency(item.approved_price) }}</td>
                    <td class="px-3 py-2 text-right">{{ formatCurrency(item.line_total) }}</td>
                  </tr>
                </tbody>
                <tfoot v-if="approvedItems.length" class="bg-slate-950/40 text-slate-200">
                  <tr>
                    <td colspan="6" class="px-3 py-2 text-right font-semibold">Grand Total</td>
                    <td class="px-3 py-2 text-right font-semibold">{{ formatCurrency(grandTotal) }}</td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>

          <div>
            <div class="mb-2 text-sm font-semibold text-slate-100">Attachment</div>
            <div v-if="purchaseOrder.attachments.length" class="space-y-2">
              <a
                v-for="attachment in purchaseOrder.attachments"
                :key="attachment.id"
                :href="attachment.url"
                target="_blank"
                rel="noopener noreferrer"
                class="flex items-center justify-between rounded border border-slate-700 bg-slate-950/40 px-3 py-2 text-sm text-slate-200 hover:border-slate-500"
              >
                <span class="truncate pr-3">{{ attachment.filename }}</span>
                <span class="shrink-0 text-xs text-slate-400">{{ attachment.size }}</span>
              </a>
            </div>
            <div v-else class="text-sm text-slate-400">Tidak ada attachment.</div>
          </div>

          <div v-if="purchaseOrder.po_comment" class="rounded border border-slate-700 bg-slate-950/40 p-3 text-sm text-slate-300">
            <div class="mb-1 text-xs uppercase tracking-wide text-slate-500">PO Comment</div>
            <div class="whitespace-pre-wrap">{{ purchaseOrder.po_comment }}</div>
          </div>

          <div v-if="purchaseOrder.po_photo_url">
            <div class="mb-2 text-sm font-semibold text-slate-100">PO File / Photo</div>
            <a
              v-if="isImageFile(purchaseOrder.po_photo_filename, purchaseOrder.po_photo_mime_type)"
              :href="purchaseOrder.po_photo_url"
              target="_blank"
              rel="noopener noreferrer"
              class="block overflow-hidden rounded border border-slate-700 bg-slate-950/40 hover:border-slate-500"
            >
              <img :src="purchaseOrder.po_photo_url" :alt="purchaseOrder.po_photo_filename || `PO Photo ${purchaseOrder.pr_number}`" class="h-56 w-full object-cover" />
            </a>
            <a
              v-else
              :href="purchaseOrder.po_photo_url"
              target="_blank"
              rel="noopener noreferrer"
              class="flex items-center justify-between rounded border border-slate-700 bg-slate-950/40 px-3 py-3 text-sm text-slate-200 hover:border-slate-500"
            >
              <span class="truncate pr-3">{{ purchaseOrder.po_photo_filename || 'File PO' }}</span>
              <span class="shrink-0 text-xs text-slate-400">Open</span>
            </a>
            <div v-if="purchaseOrder.po_photo_filename" class="mt-2 text-xs text-slate-400">
              {{ purchaseOrder.po_photo_filename }}
            </div>
          </div>

          <div v-if="purchaseOrder.can_update_po" class="space-y-3 rounded border border-slate-700 bg-slate-950/30 p-4">
            <div>
              <label class="mb-2 block text-sm font-semibold text-slate-100">Comment Purchase Order</label>
              <textarea
                v-model="form.po_comment"
                rows="3"
                class="w-full rounded border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-100"
                placeholder="Tulis comment untuk proses purchase order"
              ></textarea>
            </div>

            <div>
              <label class="mb-2 block text-sm font-semibold text-slate-100">Upload File / Foto</label>
              <input
                type="file"
                accept="image/*,.pdf,.doc,.docx,.xls,.xlsx"
                capture="environment"
                class="block w-full rounded border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-200"
                @change="selectPoUpload"
              />
              <div class="mt-2 text-xs text-slate-500">
                Di HP bisa pilih file atau ambil foto langsung bila didukung browser. Maksimal 10 MB. Format: jpg, jpeg, png, webp, pdf, doc, docx, xls, xlsx.
              </div>
              <div v-if="form.po_photo_name" class="mt-2 text-xs text-slate-400">
                File dipilih: {{ form.po_photo_name }}
              </div>
            </div>
          </div>

          <div v-if="purchaseOrder.can_process || purchaseOrder.can_update_po || purchaseOrder.status === 'done' || canDelete()" class="flex flex-col gap-2 md:flex-row md:justify-end">
            <button
              v-if="purchaseOrder.can_process"
              type="button"
              class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white"
              @click="processOrder"
            >
              Process
            </button>
            <button
              v-if="purchaseOrder.can_update_po"
              type="button"
              class="rounded bg-amber-600 px-4 py-2 text-sm font-semibold text-white"
              :disabled="processing"
              @click="submitPoForm('save')"
            >
              {{ processing ? 'Saving...' : 'Save' }}
            </button>
            <button
              v-if="purchaseOrder.can_done"
              type="button"
              class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white"
              :disabled="processing"
              @click="submitPoForm('done')"
            >
              {{ processing ? 'Saving...' : 'Done' }}
            </button>
            <button
              v-if="canDelete()"
              type="button"
              class="rounded bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700"
              @click="confirmDelete"
            >
              Reset PO
            </button>
            <div v-if="purchaseOrder.status === 'done'" class="rounded bg-sky-700/20 px-4 py-2 text-sm font-semibold text-sky-300">
              Purchase order sudah done
            </div>
          </div>
        </section>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
  purchaseOrder: { type: Object, required: true },
  currentUser: { type: Object, default: () => ({}) },
});

const page = usePage();
const processing = ref(false);
const form = ref({
  po_comment: props.purchaseOrder.po_comment || '',
  po_photo: null,
  po_photo_name: '',
});

const validationErrors = computed(() => Object.values(page.props.errors || {}).filter(Boolean));
const approvedItems = computed(() => props.purchaseOrder.po_summary?.items || []);
const grandTotal = computed(() => Number(props.purchaseOrder.po_summary?.grand_total || 0));

function processOrder() {
  router.post(`/gmisl/procurement/purchase-order/${props.purchaseOrder.id}/process`, {}, {
    preserveScroll: true,
  });
}

function selectPoUpload(event) {
  const file = event?.target?.files?.[0] || null;
  form.value.po_photo = file;
  form.value.po_photo_name = file ? file.name : '';
}

function submitPoForm(action) {
  processing.value = true;
  const formData = new FormData();
  formData.append('po_comment', form.value.po_comment || '');
  if (form.value.po_photo) {
    formData.append('po_photo', form.value.po_photo);
  }

  router.post(`/gmisl/procurement/purchase-order/${props.purchaseOrder.id}/${action}`, formData, {
    preserveScroll: true,
    forceFormData: true,
    onFinish: () => {
      processing.value = false;
    },
  });
}

function isImageFile(filename, mimeType) {
  const normalizedMime = String(mimeType || '').toLowerCase();
  if (normalizedMime.startsWith('image/')) return true;
  const extension = String(filename || '').split('.').pop()?.toLowerCase() || '';
  return ['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(extension);
}

function formatCurrency(value) {
  const number = Number(value || 0);
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 2 }).format(number);
}

function canDelete() {
  if (props.purchaseOrder?.can_delete !== true && String(props.currentUser?.department_code || '').toUpperCase() !== 'IT') {
    return false;
  }

  const normalizedStatus = String(props.purchaseOrder?.status || '').trim().toLowerCase();
  return ['approved', 'process', 'done'].includes(normalizedStatus);
}

async function confirmDelete() {
  const result = await Swal.fire({
    title: 'Reset Purchase Order?',
    text: 'Data proses PO akan dibersihkan dan status kembali ke Approved. Data PR tetap tersimpan. Lanjutkan?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Ya, Reset',
    cancelButtonText: 'Batal',
  });

  if (result.isConfirmed) {
    router.delete(`/gmisl/procurement/purchase-order/${props.purchaseOrder?.id}`, {
      preserveScroll: true,
    });
  }
}

function formatStatus(status) {
  const normalized = String(status || '').trim().toLowerCase();
  if (normalized === 'approved') return 'Approved';
  if (normalized === 'process') return 'Process';
  if (normalized === 'done') return 'Done';
  return normalized ? normalized.charAt(0).toUpperCase() + normalized.slice(1) : '-';
}

function statusClass(status) {
  const normalized = String(status || '').trim().toLowerCase();
  if (normalized === 'approved') return 'bg-emerald-700/30 text-emerald-300 border border-emerald-500/40';
  if (normalized === 'process') return 'bg-indigo-700/30 text-indigo-300 border border-indigo-500/40';
  if (normalized === 'done') return 'bg-sky-700/30 text-sky-300 border border-sky-500/40';
  return 'bg-slate-700/40 text-slate-200 border border-slate-600';
}

function formatPriority(priority) {
  const normalized = String(priority || '').trim().toLowerCase();
  if (normalized === 'urgent') return 'Urgent';
  if (normalized === 'low') return 'Low';
  return 'Medium';
}

function priorityClass(priority) {
  const normalized = String(priority || '').trim().toLowerCase();
  if (normalized === 'urgent') return 'bg-rose-700/30 text-rose-300 border border-rose-500/40';
  if (normalized === 'low') return 'bg-sky-700/30 text-sky-300 border border-sky-500/40';
  return 'bg-indigo-700/30 text-indigo-300 border border-indigo-500/40';
}
</script>
