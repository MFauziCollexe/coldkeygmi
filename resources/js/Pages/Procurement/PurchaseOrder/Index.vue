<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mx-auto max-w-6xl space-y-4">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 class="text-2xl font-bold">Purchase Order</h2>
            <p class="text-sm text-slate-400">
              PR yang sudah di-approve Owner akan masuk ke sini. Tim FAT dapat melanjutkan lewat tombol Process, isi comment, upload foto, lalu tandai Done.
            </p>
          </div>
          <div class="rounded bg-slate-800 px-4 py-2 text-sm text-slate-300">
            {{ currentUser.department_name || '-' }}
          </div>
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
          <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
              <h3 class="text-lg font-semibold text-slate-100">Queue</h3>
              <p class="text-sm text-slate-400">Daftar PR approved dan process di menu Purchase Order.</p>
            </div>
            <div class="w-full md:w-72">
              <input
                v-model="search"
                type="text"
                placeholder="Search PR, department, requestor"
                class="w-full rounded border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-100"
              />
            </div>
          </div>

          <div v-if="!filteredPurchaseOrders.length" class="rounded border border-dashed border-slate-700 bg-slate-900/40 px-4 py-8 text-center text-sm text-slate-400">
            Tidak ada data purchase order yang sesuai.
          </div>

          <div v-else class="space-y-4">
            <article v-for="order in filteredPurchaseOrders" :key="order.id" class="rounded-lg border border-slate-700 bg-slate-900/40 p-4">
              <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                  <div class="text-lg font-semibold text-slate-100">{{ order.pr_number }}</div>
                  <div class="mt-1 text-sm text-slate-400">
                    {{ order.department_name || '-' }} | {{ order.requester_name || '-' }}
                  </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                  <span class="rounded px-2 py-1 text-xs font-semibold" :class="priorityClass(order.priority)">
                    {{ formatPriority(order.priority) }}
                  </span>
                  <span class="rounded px-2 py-1 text-xs font-semibold" :class="statusClass(order.status)">
                    {{ formatStatus(order.status) }}
                  </span>
                </div>
              </div>

              <div class="mt-4 grid grid-cols-1 gap-3 text-sm text-slate-300 md:grid-cols-4">
                <div>
                  <div class="text-xs uppercase tracking-wide text-slate-500">PR Date</div>
                  <div>{{ order.pr_date || '-' }}</div>
                </div>
                <div>
                  <div class="text-xs uppercase tracking-wide text-slate-500">Request Date</div>
                  <div>{{ order.request_date || '-' }}</div>
                </div>
                <div>
                  <div class="text-xs uppercase tracking-wide text-slate-500">Approved At</div>
                  <div>{{ order.approved_at || '-' }}</div>
                </div>
                <div>
                  <div class="text-xs uppercase tracking-wide text-slate-500">Approved By</div>
                  <div>{{ order.approved_by_name || '-' }}</div>
                </div>
              </div>

              <div class="mt-4 grid grid-cols-1 gap-3 text-sm text-slate-300 md:grid-cols-2">
                <div>
                  <div class="text-xs uppercase tracking-wide text-slate-500">Processed At</div>
                  <div>{{ order.po_processed_at || '-' }}</div>
                </div>
                <div>
                  <div class="text-xs uppercase tracking-wide text-slate-500">Done At</div>
                  <div>{{ order.po_done_at || '-' }}</div>
                </div>
              </div>

              <div v-if="order.note" class="mt-4 rounded border border-slate-700 bg-slate-950/40 p-3 text-sm text-slate-300">
                <div class="mb-1 text-xs uppercase tracking-wide text-slate-500">Note</div>
                {{ order.note }}
              </div>

              <div class="mt-4">
                <div class="mb-2 text-sm font-semibold text-slate-100">Items</div>
                <div class="overflow-hidden rounded border border-slate-700">
                  <table class="w-full table-auto text-sm">
                    <thead class="bg-slate-950/50 text-left text-slate-400">
                      <tr>
                        <th class="px-3 py-2">Product</th>
                        <th class="px-3 py-2">Qty</th>
                        <th class="px-3 py-2">UoM</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="item in order.items" :key="item.id" class="border-t border-slate-700 text-slate-200">
                        <td class="px-3 py-2">{{ item.product_name }}</td>
                        <td class="px-3 py-2">{{ item.qty }}</td>
                        <td class="px-3 py-2">{{ item.uom }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <div class="mt-4">
                <div class="mb-2 text-sm font-semibold text-slate-100">Attachment</div>
                <div v-if="order.attachments.length" class="space-y-2">
                  <a
                    v-for="attachment in order.attachments"
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

              <div v-if="order.po_comment" class="mt-4 rounded border border-slate-700 bg-slate-950/40 p-3 text-sm text-slate-300">
                <div class="mb-1 text-xs uppercase tracking-wide text-slate-500">PO Comment</div>
                <div class="whitespace-pre-wrap">{{ order.po_comment }}</div>
              </div>

              <div v-if="order.po_photo_url" class="mt-4">
                <div class="mb-2 text-sm font-semibold text-slate-100">PO Photo</div>
                <a :href="order.po_photo_url" target="_blank" rel="noopener noreferrer" class="block overflow-hidden rounded border border-slate-700 bg-slate-950/40 hover:border-slate-500">
                  <img :src="order.po_photo_url" :alt="order.po_photo_filename || `PO Photo ${order.pr_number}`" class="h-56 w-full object-cover" />
                </a>
                <div v-if="order.po_photo_filename" class="mt-2 text-xs text-slate-400">
                  {{ order.po_photo_filename }}
                </div>
              </div>

              <div v-if="order.can_update_po" class="mt-4 space-y-3 rounded border border-slate-700 bg-slate-950/30 p-4">
                <div>
                  <label class="mb-2 block text-sm font-semibold text-slate-100">Comment Purchase Order</label>
                  <textarea
                    v-model="orderForms[order.id].po_comment"
                    rows="3"
                    class="w-full rounded border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-100"
                    placeholder="Tulis comment untuk proses purchase order"
                  ></textarea>
                </div>

                <div>
                  <label class="mb-2 block text-sm font-semibold text-slate-100">Upload Foto</label>
                  <input
                    type="file"
                    accept="image/*"
                    class="block w-full rounded border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-200"
                    @change="selectPoPhoto(order.id, $event)"
                  />
                  <div v-if="orderForms[order.id].po_photo_name" class="mt-2 text-xs text-slate-400">
                    File dipilih: {{ orderForms[order.id].po_photo_name }}
                  </div>
                  <div class="mt-2 text-xs text-slate-500">
                    Maksimal 10 MB, format gambar.
                  </div>
                </div>
              </div>

              <div v-if="order.can_process || order.can_update_po || order.status === 'done'" class="mt-4 flex flex-col gap-2 md:flex-row md:justify-end">
                <button
                  v-if="order.can_process"
                  type="button"
                  class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white"
                  @click="processOrder(order.id)"
                >
                  Process
                </button>
                <button
                  v-if="order.can_update_po"
                  type="button"
                  class="rounded bg-amber-600 px-4 py-2 text-sm font-semibold text-white"
                  :disabled="processingOrderId === order.id"
                  @click="savePo(order.id)"
                >
                  {{ processingOrderId === order.id ? 'Saving...' : 'Save' }}
                </button>
                <button
                  v-if="order.can_done"
                  type="button"
                  class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white"
                  :disabled="processingOrderId === order.id"
                  @click="doneOrder(order.id)"
                >
                  {{ processingOrderId === order.id ? 'Saving...' : 'Done' }}
                </button>
                <div v-if="order.status === 'done'" class="rounded bg-sky-700/20 px-4 py-2 text-sm font-semibold text-sky-300">
                  Purchase order sudah done
                </div>
              </div>
            </article>
          </div>
        </section>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  purchaseOrders: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
  currentUser: { type: Object, default: () => ({}) },
});

const search = ref(String(props.filters?.search || ''));
const page = usePage();
const processingOrderId = ref(null);
const orderForms = reactive(
  props.purchaseOrders.reduce((accumulator, order) => {
    accumulator[order.id] = {
      po_comment: order.po_comment || '',
      po_photo: null,
      po_photo_name: '',
    };
    return accumulator;
  }, {})
);

const filteredPurchaseOrders = computed(() => {
  const keyword = search.value.trim().toLowerCase();
  if (!keyword) return props.purchaseOrders;

  return props.purchaseOrders.filter((order) => {
    const haystack = [
      order.pr_number,
      order.department_name,
      order.requester_name,
      order.status,
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase();

    return haystack.includes(keyword);
  });
});

const validationErrors = computed(() =>
  Object.values(page.props.errors || {}).filter(Boolean)
);

function processOrder(id) {
  router.post(`/gmisl/procurement/purchase-order/${id}/process`, {}, {
    preserveScroll: true,
  });
}

function selectPoPhoto(id, event) {
  const file = event?.target?.files?.[0] || null;
  ensureOrderForm(id);
  orderForms[id].po_photo = file;
  orderForms[id].po_photo_name = file ? file.name : '';
}

function savePo(id) {
  submitPoForm(id, 'save');
}

function doneOrder(id) {
  submitPoForm(id, 'done');
}

function submitPoForm(id, action) {
  ensureOrderForm(id);
  processingOrderId.value = id;

  const formData = new FormData();
  formData.append('po_comment', orderForms[id].po_comment || '');

  if (orderForms[id].po_photo) {
    formData.append('po_photo', orderForms[id].po_photo);
  }

  router.post(`/gmisl/procurement/purchase-order/${id}/${action}`, formData, {
    preserveScroll: true,
    forceFormData: true,
    onFinish: () => {
      processingOrderId.value = null;
    },
  });
}

function ensureOrderForm(id) {
  if (!orderForms[id]) {
    orderForms[id] = {
      po_comment: '',
      po_photo: null,
      po_photo_name: '',
    };
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
