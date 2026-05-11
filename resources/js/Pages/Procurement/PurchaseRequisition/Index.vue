<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mx-auto max-w-6xl space-y-4">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 class="text-2xl font-bold">Purchase Requisition</h2>
            <p class="text-sm text-slate-400">Buat purchase requisition baru sesuai kebutuhan procurement.</p>
          </div>
          <div class="inline-flex items-center justify-center rounded bg-slate-600 px-4 py-2 text-sm font-semibold text-white">
            {{ statusMeta.label }}
          </div>
        </div>

        <div class="rounded border border-sky-700 bg-sky-700/10 px-4 py-3 text-sm text-sky-200">
          PR yang dibuat hanya mengikuti department pembuat. Setelah disimpan status menjadi <strong>Waiting</strong>,
          lalu bisa dilihat department <strong>Owner</strong>. Setelah di-approve Owner, PR masuk ke menu
          <strong>Purchase Order</strong> untuk diproses tim <strong>FAT</strong>.
        </div>

        <div v-if="$page.props.flash?.success" class="rounded border border-green-600 bg-green-600/20 px-4 py-3 text-sm text-green-300">
          {{ $page.props.flash.success }}
        </div>

        <form @submit.prevent="submit" class="space-y-4 rounded bg-slate-800 p-4 md:p-6">
          <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <div class="space-y-4">
              <div class="relative">
                <input
                  :value="defaults.pr_number || ''"
                  disabled
                  placeholder=" "
                  class="peer w-full rounded-lg border border-slate-700 bg-slate-800 px-3 pb-2 pt-5 text-slate-100 disabled:opacity-100"
                />
                <label
                  class="pointer-events-none absolute left-3 top-0 z-10 -translate-y-1/2 bg-slate-800 px-1 text-xs text-slate-300 transition-all"
                >
                  PR Number
                </label>
              </div>

              <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="relative group">
                  <EnhancedDatePicker
                    :model-value="defaults.pr_date || ''"
                    disabled
                    placeholder=" "
                    input-class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 pb-2 pt-5 placeholder-transparent"
                  />
                  <label
                    class="pointer-events-none absolute left-3 top-0 z-10 -translate-y-1/2 bg-slate-800 px-1 text-xs text-slate-300 transition-all"
                  >
                    PR Date
                  </label>
                </div>

                <div class="relative group">
                  <EnhancedDatePicker
                    v-model="form.request_date"
                    placeholder=" "
                    input-class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 pb-2 pt-5 placeholder-transparent"
                  />
                  <label
                    :class="[
                      'pointer-events-none absolute left-3 z-10 transition-all',
                      (form.request_date
                        ? 'top-0 -translate-y-1/2 bg-slate-800 px-1 text-xs text-slate-300'
                        : 'top-1/2 -translate-y-1/2 bg-transparent px-0 text-base text-slate-400'),
                      'group-focus-within:top-0 group-focus-within:-translate-y-1/2 group-focus-within:bg-slate-800 group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200',
                    ]"
                  >
                    Request Date
                  </label>
                  <div v-if="form.errors.request_date" class="mt-1 text-xs text-rose-300">{{ form.errors.request_date }}</div>
                </div>
              </div>

              <div class="relative group">
                <select
                  v-model="form.priority"
                  class="peer w-full rounded-lg border border-slate-700 bg-slate-800 px-3 pb-2 pt-5 text-slate-100 focus:border-indigo-500 focus:outline-none"
                >
                  <option value="medium">Medium</option>
                  <option value="urgent">Urgent</option>
                  <option value="low">Low</option>
                </select>
                <label
                  class="pointer-events-none absolute left-3 top-0 z-10 -translate-y-1/2 bg-slate-800 px-1 text-xs text-slate-300 transition-all"
                >
                  Priority
                </label>
                <div v-if="form.errors.priority" class="mt-1 text-xs text-rose-300">{{ form.errors.priority }}</div>
              </div>
            </div>

            <div class="space-y-4">
              <div class="relative">
                <input
                  :value="currentUser.name || ''"
                  disabled
                  placeholder=" "
                  class="peer w-full rounded-lg border border-slate-700 bg-slate-800 px-3 pb-2 pt-5 text-slate-100 disabled:opacity-100"
                />
                <label
                  class="pointer-events-none absolute left-3 top-0 z-10 -translate-y-1/2 bg-slate-800 px-1 text-xs text-slate-300 transition-all"
                >
                  Requestor
                </label>
              </div>

              <div class="relative">
                <input
                  :value="currentUser.department_name || ''"
                  disabled
                  placeholder=" "
                  class="peer w-full rounded-lg border border-slate-700 bg-slate-800 px-3 pb-2 pt-5 text-slate-100 disabled:opacity-100"
                />
                <label
                  class="pointer-events-none absolute left-3 top-0 z-10 -translate-y-1/2 bg-slate-800 px-1 text-xs text-slate-300 transition-all"
                >
                  Department
                </label>
                <div v-if="form.errors.department_id" class="mt-1 text-xs text-rose-300">{{ form.errors.department_id }}</div>
              </div>
            </div>
          </div>

          <div class="overflow-hidden rounded-lg border border-slate-700">
            <div class="flex items-center justify-between border-b border-slate-700 bg-slate-900 px-4 py-3">
              <h3 class="font-semibold text-slate-100">Items</h3>
              <button type="button" class="rounded bg-indigo-600 px-3 py-2 text-sm text-white" @click="addItem">
                Add Item
              </button>
            </div>

            <div class="hidden md:block">
              <table class="w-full table-auto">
                <thead class="bg-slate-900/70 text-left text-slate-400">
                  <tr>
                    <th class="px-4 py-3">No.</th>
                    <th class="px-4 py-3">Product Name</th>
                    <th class="px-4 py-3">Qty</th>
                    <th class="px-4 py-3">UoM</th>
                    <th class="px-4 py-3 text-right">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, index) in form.items" :key="index" class="border-t border-slate-700">
                    <td class="px-4 py-3 align-top text-slate-300">{{ index + 1 }}</td>
                    <td class="px-4 py-3 align-top">
                      <input v-model="item.product_name" type="text" class="w-full rounded-none border-0 border-b border-slate-600 bg-transparent px-3 py-2 text-slate-100 shadow-none focus:border-slate-400 focus:outline-none focus:ring-0" />
                      <div v-if="itemError(index, 'product_name')" class="mt-1 text-xs text-rose-300">{{ itemError(index, 'product_name') }}</div>
                    </td>
                    <td class="px-4 py-3 align-top">
                      <input v-model="item.qty" type="number" min="0" step="0.01" class="w-full rounded-none border-0 border-b border-slate-600 bg-transparent px-3 py-2 text-slate-100 shadow-none focus:border-slate-400 focus:outline-none focus:ring-0" />
                      <div v-if="itemError(index, 'qty')" class="mt-1 text-xs text-rose-300">{{ itemError(index, 'qty') }}</div>
                    </td>
                    <td class="px-4 py-3 align-top">
                      <select v-model="item.uom" class="w-full rounded-none border-0 border-b border-slate-600 bg-transparent px-3 py-2 text-slate-100 shadow-none focus:border-slate-400 focus:outline-none focus:ring-0">
                        <option value="" class="bg-slate-800 text-slate-100">Pilih UoM</option>
                        <option v-for="unit in uomOptions" :key="unit.id" :value="unit.name" class="bg-slate-800 text-slate-100">
                          {{ unit.name }}
                        </option>
                      </select>
                      <div v-if="itemError(index, 'uom')" class="mt-1 text-xs text-rose-300">{{ itemError(index, 'uom') }}</div>
                    </td>
                    <td class="px-4 py-3 text-right align-top">
                      <button type="button" class="rounded bg-rose-600 px-3 py-2 text-sm text-white" @click="removeItem(index)">
                        Remove
                      </button>
                    </td>
                  </tr>
                  <tr v-if="!form.items.length">
                    <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-400">
                      Belum ada item. Klik Add Item untuk menambahkan baris.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="space-y-4 p-4 md:hidden">
              <div v-if="!form.items.length" class="rounded border border-dashed border-slate-700 bg-slate-900/40 p-4 text-center text-sm text-slate-400">
                Belum ada item. Klik Add Item untuk menambahkan baris.
              </div>
              <div v-for="(item, index) in form.items" :key="`mobile-${index}`" class="rounded border border-slate-700 bg-slate-900/40 p-4">
                <div class="mb-3 flex items-center justify-between">
                  <div class="font-semibold text-slate-100">Item {{ index + 1 }}</div>
                  <button type="button" class="rounded bg-rose-600 px-3 py-2 text-xs text-white" @click="removeItem(index)">
                    Remove
                  </button>
                </div>
                <div class="space-y-3">
                  <div>
                    <label class="mb-1 block text-sm text-slate-400">Product Name</label>
                    <input v-model="item.product_name" type="text" class="w-full rounded-none border-0 border-b border-slate-600 bg-transparent px-3 py-2 text-slate-100 shadow-none focus:border-slate-400 focus:outline-none focus:ring-0" />
                    <div v-if="itemError(index, 'product_name')" class="mt-1 text-xs text-rose-300">{{ itemError(index, 'product_name') }}</div>
                  </div>
                  <div>
                    <label class="mb-1 block text-sm text-slate-400">Qty</label>
                    <input v-model="item.qty" type="number" min="0" step="0.01" class="w-full rounded-none border-0 border-b border-slate-600 bg-transparent px-3 py-2 text-slate-100 shadow-none focus:border-slate-400 focus:outline-none focus:ring-0" />
                    <div v-if="itemError(index, 'qty')" class="mt-1 text-xs text-rose-300">{{ itemError(index, 'qty') }}</div>
                  </div>
                  <div>
                    <label class="mb-1 block text-sm text-slate-400">UoM</label>
                    <select v-model="item.uom" class="w-full rounded-none border-0 border-b border-slate-600 bg-transparent px-3 py-2 text-slate-100 shadow-none focus:border-slate-400 focus:outline-none focus:ring-0">
                      <option value="" class="bg-slate-800 text-slate-100">Pilih UoM</option>
                      <option v-for="unit in uomOptions" :key="`mobile-${unit.id}`" :value="unit.name" class="bg-slate-800 text-slate-100">
                        {{ unit.name }}
                      </option>
                    </select>
                    <div v-if="itemError(index, 'uom')" class="mt-1 text-xs text-rose-300">{{ itemError(index, 'uom') }}</div>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="form.errors.items" class="border-t border-slate-700 px-4 py-3 text-xs text-rose-300">
              {{ form.errors.items }}
            </div>
          </div>

          <div class="space-y-3 rounded-lg border border-slate-700 p-4">
            <div>
              <label class="block text-sm font-medium text-slate-200">Attachment</label>
              <p class="mt-1 text-xs text-slate-400">Bisa upload foto atau file pendukung. Maksimal 10 MB per file.</p>
            </div>

            <div
              class="cursor-pointer rounded-lg border-2 border-dashed p-5 text-center transition md:p-8"
              :class="dragActive ? 'border-indigo-500 bg-slate-700/40' : 'border-slate-600 bg-slate-900/30'"
              @click="clickFileInput"
              @dragover.prevent="onDragOver"
              @dragleave.prevent="onDragLeave"
              @drop.prevent="onDrop"
            >
              <p class="mb-2 font-medium text-slate-300">Drag & drop file di sini</p>
              <p class="text-sm text-indigo-300">atau klik untuk pilih beberapa file</p>
              <input
                ref="fileInput"
                type="file"
                multiple
                accept="image/*,application/pdf,.doc,.docx,.xls,.xlsx"
                @change="handleFileUpload"
                class="hidden"
              />
            </div>

            <div v-if="form.attachments.length" class="space-y-2">
              <div
                v-for="(file, index) in form.attachments"
                :key="`${file.name}-${index}`"
                class="flex items-center justify-between rounded bg-slate-900 px-3 py-2 text-sm"
              >
                <div class="min-w-0">
                  <div class="truncate text-slate-100">{{ file.name }}</div>
                  <div class="text-xs text-slate-400">{{ formatLocalFileSize(file.size) }}</div>
                </div>
                <button type="button" class="rounded bg-rose-600 px-2 py-1 text-xs text-white" @click="removeAttachment(index)">
                  Remove
                </button>
              </div>
            </div>

            <div v-if="attachmentErrorList.length" class="space-y-1">
              <div v-for="(error, index) in attachmentErrorList" :key="`attachment-error-${index}`" class="text-xs text-rose-300">
                {{ error }}
              </div>
            </div>
          </div>

          <div class="relative">
            <textarea
              v-model="form.note"
              rows="4"
              placeholder=" "
              class="peer w-full rounded-lg border border-slate-700 bg-slate-800 px-3 pb-2 pt-6"
            ></textarea>
            <label
              class="pointer-events-none absolute left-3 top-0 z-10 -translate-y-1/2 bg-slate-800 px-1 text-xs text-slate-300 transition-all peer-placeholder-shown:top-4 peer-placeholder-shown:translate-y-0 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:bg-slate-800 peer-focus:px-1 peer-focus:text-xs peer-focus:text-slate-200"
            >
              Note
            </label>
            <div v-if="form.errors.note" class="mt-1 text-xs text-rose-300">{{ form.errors.note }}</div>
          </div>

          <div class="flex justify-end">
            <button type="submit" class="w-full rounded bg-indigo-600 px-4 py-2 text-white md:w-auto" :disabled="form.processing || !currentUser.department_id">
              {{ form.processing ? 'Saving...' : 'Save' }}
            </button>
          </div>
        </form>

        <section class="space-y-4 rounded bg-slate-800 p-4 md:p-6">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-semibold text-slate-100">PR List</h3>
              <p class="text-sm text-slate-400">Daftar PR yang terlihat untuk department pembuat dan department Owner.</p>
            </div>
            <div class="text-sm text-slate-400">{{ purchaseRequisitions.length }} data</div>
          </div>

          <div v-if="!purchaseRequisitions.length" class="rounded border border-dashed border-slate-700 bg-slate-900/40 px-4 py-8 text-center text-sm text-slate-400">
            Belum ada purchase requisition yang bisa dilihat.
          </div>

          <div v-else class="space-y-4">
            <article v-for="requisition in purchaseRequisitions" :key="requisition.id" class="rounded-lg border border-slate-700 bg-slate-900/40 p-4">
              <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                  <div class="text-lg font-semibold text-slate-100">{{ requisition.pr_number }}</div>
                  <div class="mt-1 text-sm text-slate-400">
                    {{ requisition.department_name || '-' }} | {{ requisition.requester_name || '-' }}
                  </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                  <span class="rounded px-2 py-1 text-xs font-semibold" :class="priorityClass(requisition.priority)">
                    {{ formatPriority(requisition.priority) }}
                  </span>
                  <span class="rounded px-2 py-1 text-xs font-semibold" :class="statusClass(requisition.status)">
                    {{ formatStatus(requisition.status) }}
                  </span>
                </div>
              </div>

              <div class="mt-4 grid grid-cols-1 gap-3 text-sm text-slate-300 md:grid-cols-4">
                <div>
                  <div class="text-xs uppercase tracking-wide text-slate-500">PR Date</div>
                  <div>{{ requisition.pr_date || '-' }}</div>
                </div>
                <div>
                  <div class="text-xs uppercase tracking-wide text-slate-500">Request Date</div>
                  <div>{{ requisition.request_date || '-' }}</div>
                </div>
                <div>
                  <div class="text-xs uppercase tracking-wide text-slate-500">Created</div>
                  <div>{{ requisition.created_at || '-' }}</div>
                </div>
                <div>
                  <div class="text-xs uppercase tracking-wide text-slate-500">Approved</div>
                  <div>{{ requisition.approved_at || '-' }}</div>
                  <div v-if="requisition.approved_by_name" class="text-xs text-slate-400">
                    by {{ requisition.approved_by_name }}
                  </div>
                </div>
              </div>

              <div v-if="requisition.note" class="mt-4 rounded border border-slate-700 bg-slate-950/40 p-3 text-sm text-slate-300">
                <div class="mb-1 text-xs uppercase tracking-wide text-slate-500">Note</div>
                {{ requisition.note }}
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
                      <tr v-for="item in requisition.items" :key="item.id" class="border-t border-slate-700 text-slate-200">
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
                <div v-if="requisition.attachments.length" class="space-y-2">
                  <a
                    v-for="attachment in requisition.attachments"
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

              <div v-if="requisition.can_approve" class="mt-4 flex justify-end">
                <button type="button" class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white" @click="approve(requisition.id)">
                  Approve
                </button>
              </div>
            </article>
          </div>
        </section>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EnhancedDatePicker from '@/Components/EnhancedDatePicker.vue';

const props = defineProps({
  defaults: { type: Object, default: () => ({}) },
  uomOptions: { type: Array, default: () => [] },
  currentUser: { type: Object, default: () => ({}) },
  purchaseRequisitions: { type: Array, default: () => [] },
});

const form = useForm({
  request_date: String(props.defaults?.request_date || ''),
  priority: String(props.defaults?.priority || 'medium'),
  department_id: props.defaults?.department_id || '',
  note: '',
  items: [],
  attachments: [],
});

const fileInput = ref(null);
const dragActive = ref(false);

const statusMeta = computed(() => {
  const normalizedStatus = String(props.defaults?.status || 'draft').trim().toLowerCase();

  if (normalizedStatus === 'approved') {
    return { label: 'Approved' };
  }

  if (normalizedStatus === 'waiting') {
    return { label: 'Waiting' };
  }

  return { label: 'Draft' };
});

const attachmentErrorList = computed(() =>
  Object.entries(form.errors)
    .filter(([key]) => key === 'attachments' || key.startsWith('attachments.'))
    .map(([, value]) => value)
);

function createEmptyItem() {
  return {
    product_name: '',
    uom: '',
    qty: '',
  };
}

function addItem() {
  form.items.push(createEmptyItem());
}

function removeItem(index) {
  form.items.splice(index, 1);
}

function itemError(index, field) {
  return form.errors[`items.${index}.${field}`] || '';
}

function clickFileInput() {
  if (fileInput.value) fileInput.value.click();
}

function handleFileUpload(event) {
  const files = Array.from(event?.target?.files || []);
  form.attachments = [...form.attachments, ...files];
  event.target.value = '';
}

function onDragOver() {
  dragActive.value = true;
}

function onDragLeave() {
  dragActive.value = false;
}

function onDrop(event) {
  dragActive.value = false;
  const files = Array.from(event?.dataTransfer?.files || []);
  form.attachments = [...form.attachments, ...files];
}

function removeAttachment(index) {
  form.attachments.splice(index, 1);
}

function submit() {
  form.post('/gmisl/procurement/purchase-requisition', {
    preserveScroll: true,
    forceFormData: true,
  });
}

function approve(id) {
  router.post(`/gmisl/procurement/purchase-requisition/${id}/approve`, {}, {
    preserveScroll: true,
  });
}

function formatStatus(status) {
  const normalized = String(status || '').trim().toLowerCase();
  if (normalized === 'approved') return 'Approved';
  if (normalized === 'waiting') return 'Waiting';
  if (normalized === 'process') return 'Process';
  if (normalized === 'draft' || normalized === 'pr') return 'Draft';
  return normalized ? normalized.charAt(0).toUpperCase() + normalized.slice(1) : '-';
}

function statusClass(status) {
  const normalized = String(status || '').trim().toLowerCase();
  if (normalized === 'approved') return 'bg-emerald-700/30 text-emerald-300 border border-emerald-500/40';
  if (normalized === 'waiting') return 'bg-amber-700/30 text-amber-300 border border-amber-500/40';
  if (normalized === 'process') return 'bg-indigo-700/30 text-indigo-300 border border-indigo-500/40';
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

function formatLocalFileSize(size) {
  const numericSize = Number(size || 0);
  if (!numericSize) return '0 B';

  const units = ['B', 'KB', 'MB', 'GB'];
  let value = numericSize;
  let unitIndex = 0;

  while (value >= 1024 && unitIndex < units.length - 1) {
    value /= 1024;
    unitIndex += 1;
  }

  return `${value.toFixed(unitIndex === 0 ? 0 : 2)} ${units[unitIndex]}`;
}
</script>
