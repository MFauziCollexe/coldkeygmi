<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mx-auto max-w-6xl space-y-4">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 class="text-2xl font-bold">Edit Purchase Requisition</h2>
            <p class="text-sm text-slate-400">Update PR yang masih berstatus waiting.</p>
          </div>
          <Link href="/gmisl/procurement/purchase-requisition" class="text-sm text-indigo-400">Back to list</Link>
        </div>

        <div class="rounded border border-amber-700 bg-amber-700/10 px-4 py-3 text-sm text-amber-200">
          Hanya PR dengan status <strong>Waiting</strong> yang bisa diedit oleh pembuatnya.
        </div>

        <form @submit.prevent="submit" class="space-y-4 rounded bg-slate-800 p-4 md:p-6">
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
                    v-model="form.request_date"
                    placeholder="dd/mm/yyyy"
                    input-class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-3 text-slate-100 placeholder-transparent"
                  />
                  <div v-if="form.errors.request_date" class="mt-1 text-xs text-rose-300">{{ form.errors.request_date }}</div>
                </div>
              </div>

              <div>
                <label class="mb-1 block text-sm text-slate-300">Priority</label>
                <select v-model="form.priority" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-3 text-slate-100">
                  <option value="medium">Medium</option>
                  <option value="urgent">Urgent</option>
                  <option value="low">Low</option>
                </select>
                <div v-if="form.errors.priority" class="mt-1 text-xs text-rose-300">{{ form.errors.priority }}</div>
              </div>
            </div>

            <div class="space-y-4">
              <div>
                <label class="mb-1 block text-sm text-slate-300">Requestor</label>
                <input :value="currentUser.name || ''" disabled class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-3 text-slate-100 disabled:opacity-100" />
              </div>
              <div>
                <label class="mb-1 block text-sm text-slate-300">Department</label>
                <input :value="currentUser.department_name || ''" disabled class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-3 text-slate-100 disabled:opacity-100" />
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

            <div class="space-y-4 p-4">
              <div v-for="(item, index) in form.items" :key="index" class="rounded border border-slate-700 bg-slate-900/40 p-4">
                <div class="mb-3 flex items-center justify-between">
                  <div class="font-semibold text-slate-100">Item {{ index + 1 }}</div>
                  <button type="button" class="rounded bg-rose-600 px-3 py-2 text-xs text-white" @click="removeItem(index)">
                    Remove
                  </button>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                  <div>
                    <label class="mb-1 block text-sm text-slate-400">Product Name</label>
                    <input v-model="item.product_name" type="text" class="w-full rounded border border-slate-700 bg-slate-800 px-3 py-2 text-slate-100" />
                    <div v-if="itemError(index, 'product_name')" class="mt-1 text-xs text-rose-300">{{ itemError(index, 'product_name') }}</div>
                  </div>
                  <div>
                    <label class="mb-1 block text-sm text-slate-400">Qty</label>
                    <input v-model="item.qty" type="number" min="0" step="0.01" class="w-full rounded border border-slate-700 bg-slate-800 px-3 py-2 text-slate-100" />
                    <div v-if="itemError(index, 'qty')" class="mt-1 text-xs text-rose-300">{{ itemError(index, 'qty') }}</div>
                  </div>
                  <div>
                    <label class="mb-1 block text-sm text-slate-400">UoM</label>
                    <select v-model="item.uom" class="w-full rounded border border-slate-700 bg-slate-800 px-3 py-2 text-slate-100">
                      <option value="">Pilih UoM</option>
                      <option v-for="unit in uomOptions" :key="unit.id" :value="unit.name">{{ unit.name }}</option>
                    </select>
                    <div v-if="itemError(index, 'uom')" class="mt-1 text-xs text-rose-300">{{ itemError(index, 'uom') }}</div>
                  </div>
                </div>
              </div>
              <div v-if="form.errors.items" class="text-xs text-rose-300">{{ form.errors.items }}</div>
            </div>
          </div>

          <div class="space-y-4 rounded-lg border border-slate-700 p-4">
            <div>
              <label class="block text-sm font-medium text-slate-200">Existing Attachment</label>
              <p class="mt-1 text-xs text-slate-400">Attachment lama bisa dihapus dari form edit ini.</p>
            </div>

            <div v-if="existingAttachments.length" class="space-y-2">
              <div v-for="attachment in existingAttachments" :key="attachment.id" class="flex items-center justify-between rounded bg-slate-900 px-3 py-2 text-sm">
                <a :href="attachment.url" target="_blank" rel="noopener noreferrer" class="min-w-0 truncate text-slate-100 hover:text-indigo-300">
                  {{ attachment.filename }}
                </a>
                <button type="button" class="rounded bg-rose-600 px-2 py-1 text-xs text-white" @click="removeExistingAttachment(attachment)">
                  Remove
                </button>
              </div>
            </div>
            <div v-else class="text-sm text-slate-400">Tidak ada attachment lama.</div>
          </div>

          <div class="space-y-3 rounded-lg border border-slate-700 p-4">
            <div>
              <label class="block text-sm font-medium text-slate-200">Add New Attachment</label>
              <p class="mt-1 text-xs text-slate-400">Bisa upload foto atau file pendukung baru. Maksimal 10 MB per file.</p>
            </div>
            <input ref="fileInput" type="file" multiple accept="image/*,application/pdf,.doc,.docx,.xls,.xlsx" @change="handleFileUpload" class="block w-full rounded border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-200" />
            <div v-if="form.attachments.length" class="space-y-2">
              <div v-for="(file, index) in form.attachments" :key="`${file.name}-${index}`" class="flex items-center justify-between rounded bg-slate-900 px-3 py-2 text-sm">
                <div class="min-w-0">
                  <div class="truncate text-slate-100">{{ file.name }}</div>
                  <div class="text-xs text-slate-400">{{ formatLocalFileSize(file.size) }}</div>
                </div>
                <button type="button" class="rounded bg-rose-600 px-2 py-1 text-xs text-white" @click="removeAttachment(index)">
                  Remove
                </button>
              </div>
            </div>
          </div>

          <div>
            <label class="mb-1 block text-sm text-slate-300">Note</label>
            <textarea v-model="form.note" rows="4" class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-3 text-slate-100"></textarea>
            <div v-if="form.errors.note" class="mt-1 text-xs text-rose-300">{{ form.errors.note }}</div>
          </div>

          <div class="flex flex-col-reverse gap-3 border-t border-slate-700 pt-4 sm:flex-row sm:justify-end">
            <Link href="/gmisl/procurement/purchase-requisition" class="rounded bg-slate-700 px-4 py-2 text-center text-white hover:bg-slate-600">Cancel</Link>
            <button type="submit" class="rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700" :disabled="form.processing">
              {{ form.processing ? 'Updating...' : 'Update' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EnhancedDatePicker from '@/Components/EnhancedDatePicker.vue';
import { ref } from 'vue';

const props = defineProps({
  purchaseRequisition: { type: Object, required: true },
  uomOptions: { type: Array, default: () => [] },
  currentUser: { type: Object, default: () => ({}) },
});

const existingAttachments = ref([...(props.purchaseRequisition.attachments || [])]);
const fileInput = ref(null);
const form = useForm({
  request_date: String(props.purchaseRequisition.request_date || ''),
  priority: String(props.purchaseRequisition.priority || 'medium'),
  department_id: props.currentUser.department_id || '',
  note: props.purchaseRequisition.note || '',
  items: (props.purchaseRequisition.items || []).map((item) => ({
    product_name: item.product_name || '',
    uom: item.uom || '',
    qty: item.qty || '',
  })),
  attachments: [],
  delete_attachment_ids: [],
});

if (!form.items.length) {
  form.items.push({ product_name: '', uom: '', qty: '' });
}

function addItem() {
  form.items.push({ product_name: '', uom: '', qty: '' });
}

function removeItem(index) {
  form.items.splice(index, 1);
}

function itemError(index, field) {
  return form.errors[`items.${index}.${field}`] || '';
}

function handleFileUpload(event) {
  const files = Array.from(event?.target?.files || []);
  form.attachments = [...form.attachments, ...files];
  event.target.value = '';
}

function removeAttachment(index) {
  form.attachments.splice(index, 1);
}

function removeExistingAttachment(attachment) {
  form.delete_attachment_ids.push(attachment.id);
  existingAttachments.value = existingAttachments.value.filter((item) => item.id !== attachment.id);
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

function formatDisplayDate(value) {
  const text = String(value || '').trim();
  if (!text || !text.includes('-')) return text;
  const [year, month, day] = text.split('-');
  if (!year || !month || !day) return text;
  return `${day}/${month}/${year}`;
}

function submit() {
  form.transform((data) => ({
    ...data,
    _method: 'put',
  }));

  form.post(`/gmisl/procurement/purchase-requisition/${props.purchaseRequisition.id}`, {
    preserveScroll: true,
    forceFormData: true,
    onFinish: () => {
      form.transform((data) => data);
    },
  });
}
</script>
