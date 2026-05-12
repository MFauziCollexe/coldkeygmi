<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mx-auto max-w-6xl space-y-4">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 class="text-2xl font-bold">Create Purchase Requisition</h2>
            <p class="text-sm text-slate-400">Buat purchase requisition baru sesuai kebutuhan procurement.</p>
          </div>
          <Link href="/gmisl/procurement/purchase-requisition" class="text-sm text-indigo-400">Back to list</Link>
        </div>

        <div class="rounded border border-sky-700 bg-sky-700/10 px-4 py-3 text-sm text-sky-200">
          PR yang dibuat hanya mengikuti department pembuat. Setelah disimpan status menjadi <strong>Waiting</strong>,
          lalu bisa dilihat department <strong>Owner</strong>. Setelah di-approve Owner, PR masuk ke menu
          <strong>Purchase Order</strong> untuk diproses tim <strong>FAT</strong>.
        </div>

        <form @submit.prevent="submit" class="space-y-4 rounded bg-slate-800 p-4 md:p-6">
          <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <div class="space-y-4">
              <FieldReadOnly label="PR Number" :value="defaults.pr_number || ''" />

              <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="relative pt-0">
                  <label class="absolute left-3 -top-0.5 z-10 -translate-y-1/2 bg-slate-800 px-1 text-sm text-slate-300">PR Date</label>
                  <EnhancedDatePicker
                    :model-value="defaults.pr_date || ''"
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
              <FieldReadOnly label="Requestor" :value="currentUser.name || ''" />
              <FieldReadOnly label="Department" :value="currentUser.department_name || ''">
                <template #error>
                  <div v-if="form.errors.department_id" class="mt-1 text-xs text-rose-300">{{ form.errors.department_id }}</div>
                </template>
              </FieldReadOnly>
            </div>
          </div>

          <ItemEditor :form="form" :uom-options="uomOptions" />
          <AttachmentUploader :form="form" />
          <NoteField :form="form" />

          <div class="flex flex-col-reverse gap-3 border-t border-slate-700 pt-4 sm:flex-row sm:justify-end">
            <Link href="/gmisl/procurement/purchase-requisition" class="rounded bg-slate-700 px-4 py-2 text-center text-white hover:bg-slate-600">Cancel</Link>
            <button type="submit" class="rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700" :disabled="form.processing || !currentUser.department_id">
              {{ form.processing ? 'Saving...' : 'Save' }}
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
import { defineComponent, h, ref, computed } from 'vue';

const props = defineProps({
  defaults: { type: Object, default: () => ({}) },
  uomOptions: { type: Array, default: () => [] },
  currentUser: { type: Object, default: () => ({}) },
});

const form = useForm({
  request_date: String(props.defaults?.request_date || ''),
  priority: String(props.defaults?.priority || 'medium'),
  department_id: props.defaults?.department_id || '',
  note: '',
  items: [],
  attachments: [],
});

function submit() {
  form.post('/gmisl/procurement/purchase-requisition', {
    preserveScroll: true,
    forceFormData: true,
  });
}

function formatDisplayDate(value) {
  const text = String(value || '').trim();
  if (!text || !text.includes('-')) return text;
  const [year, month, day] = text.split('-');
  if (!year || !month || !day) return text;
  return `${day}/${month}/${year}`;
}

const FieldReadOnly = defineComponent({
  props: { label: String, value: String },
  setup(props, { slots }) {
    return () => h('div', [
      h('label', { class: 'mb-1 block text-sm text-slate-300' }, props.label || ''),
      h('input', { value: props.value || '', disabled: true, class: 'w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-3 text-slate-100 disabled:opacity-100' }),
      slots.error?.(),
    ]);
  },
});

const ItemEditor = defineComponent({
  props: { form: Object, uomOptions: Array },
  setup(props) {
    function createEmptyItem() {
      return { product_name: '', uom: '', qty: '' };
    }
    if (!props.form.items.length) {
      props.form.items.push(createEmptyItem());
    }
    function addItem() {
      props.form.items.push(createEmptyItem());
    }
    function removeItem(index) {
      props.form.items.splice(index, 1);
    }
    function itemError(index, field) {
      return props.form.errors[`items.${index}.${field}`] || '';
    }
    return () => h('div', { class: 'overflow-hidden rounded-lg border border-slate-700' }, [
      h('div', { class: 'flex items-center justify-between border-b border-slate-700 bg-slate-900 px-4 py-3' }, [
        h('h3', { class: 'font-semibold text-slate-100' }, 'Items'),
        h('button', { type: 'button', class: 'rounded bg-indigo-600 px-3 py-2 text-sm text-white', onClick: addItem }, 'Add Item'),
      ]),
      h('div', { class: 'space-y-4 p-4' }, [
        ...props.form.items.map((item, index) =>
          h('div', { class: 'rounded border border-slate-700 bg-slate-900/40 p-4', key: index }, [
            h('div', { class: 'mb-3 flex items-center justify-between' }, [
              h('div', { class: 'font-semibold text-slate-100' }, `Item ${index + 1}`),
              h('button', { type: 'button', class: 'rounded bg-rose-600 px-3 py-2 text-xs text-white', onClick: () => removeItem(index) }, 'Remove'),
            ]),
            h('div', { class: 'grid grid-cols-1 gap-4 md:grid-cols-3' }, [
              h('div', [
                h('label', { class: 'mb-1 block text-sm text-slate-400' }, 'Product Name'),
                h('input', {
                  value: item.product_name,
                  onInput: (event) => { item.product_name = event.target.value; },
                  class: 'w-full rounded border border-slate-700 bg-slate-800 px-3 py-2 text-slate-100',
                }),
                itemError(index, 'product_name') ? h('div', { class: 'mt-1 text-xs text-rose-300' }, itemError(index, 'product_name')) : null,
              ]),
              h('div', [
                h('label', { class: 'mb-1 block text-sm text-slate-400' }, 'Qty'),
                h('input', {
                  value: item.qty,
                  type: 'number',
                  min: '0',
                  step: '0.01',
                  onInput: (event) => { item.qty = event.target.value; },
                  class: 'w-full rounded border border-slate-700 bg-slate-800 px-3 py-2 text-slate-100',
                }),
                itemError(index, 'qty') ? h('div', { class: 'mt-1 text-xs text-rose-300' }, itemError(index, 'qty')) : null,
              ]),
              h('div', [
                h('label', { class: 'mb-1 block text-sm text-slate-400' }, 'UoM'),
                h('select', {
                  value: item.uom,
                  onChange: (event) => { item.uom = event.target.value; },
                  class: 'w-full rounded border border-slate-700 bg-slate-800 px-3 py-2 text-slate-100',
                }, [
                  h('option', { value: '' }, 'Pilih UoM'),
                  ...(props.uomOptions || []).map((unit) => h('option', { value: unit.name, key: unit.id }, unit.name)),
                ]),
                itemError(index, 'uom') ? h('div', { class: 'mt-1 text-xs text-rose-300' }, itemError(index, 'uom')) : null,
              ]),
            ]),
          ])
        ),
        props.form.errors.items ? h('div', { class: 'text-xs text-rose-300' }, props.form.errors.items) : null,
      ]),
    ]);
  },
});

const AttachmentUploader = defineComponent({
  props: { form: Object },
  setup(props) {
    const fileInput = ref(null);
    const dragActive = ref(false);
    const attachmentErrorList = computed(() =>
      Object.entries(props.form.errors)
        .filter(([key]) => key === 'attachments' || key.startsWith('attachments.'))
        .map(([, value]) => value)
    );
    function clickFileInput() {
      if (fileInput.value) fileInput.value.click();
    }
    function handleFileUpload(event) {
      const files = Array.from(event?.target?.files || []);
      props.form.attachments = [...props.form.attachments, ...files];
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
      props.form.attachments = [...props.form.attachments, ...files];
    }
    function removeAttachment(index) {
      props.form.attachments.splice(index, 1);
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
    return () => h('div', { class: 'space-y-3 rounded-lg border border-slate-700 p-4' }, [
      h('div', [
        h('label', { class: 'block text-sm font-medium text-slate-200' }, 'Attachment'),
        h('p', { class: 'mt-1 text-xs text-slate-400' }, 'Bisa upload foto atau file pendukung. Maksimal 10 MB per file.'),
      ]),
      h('div', {
        class: `cursor-pointer rounded-lg border-2 border-dashed p-5 text-center transition md:p-8 ${dragActive.value ? 'border-indigo-500 bg-slate-700/40' : 'border-slate-600 bg-slate-900/30'}`,
        onClick: clickFileInput,
        onDragover: (e) => { e.preventDefault(); onDragOver(); },
        onDragleave: (e) => { e.preventDefault(); onDragLeave(); },
        onDrop: (e) => { e.preventDefault(); onDrop(e); },
      }, [
        h('p', { class: 'mb-2 font-medium text-slate-300' }, 'Drag & drop file di sini'),
        h('p', { class: 'text-sm text-indigo-300' }, 'atau klik untuk pilih beberapa file'),
        h('input', {
          ref: fileInput,
          type: 'file',
          multiple: true,
          accept: 'image/*,application/pdf,.doc,.docx,.xls,.xlsx',
          onChange: handleFileUpload,
          class: 'hidden',
        }),
      ]),
      props.form.attachments.length
        ? h('div', { class: 'space-y-2' }, props.form.attachments.map((file, index) =>
            h('div', { class: 'flex items-center justify-between rounded bg-slate-900 px-3 py-2 text-sm', key: `${file.name}-${index}` }, [
              h('div', { class: 'min-w-0' }, [
                h('div', { class: 'truncate text-slate-100' }, file.name),
                h('div', { class: 'text-xs text-slate-400' }, formatLocalFileSize(file.size)),
              ]),
              h('button', { type: 'button', class: 'rounded bg-rose-600 px-2 py-1 text-xs text-white', onClick: () => removeAttachment(index) }, 'Remove'),
            ])
          ))
        : null,
      attachmentErrorList.value.length
        ? h('div', { class: 'space-y-1' }, attachmentErrorList.value.map((error, index) => h('div', { class: 'text-xs text-rose-300', key: `attachment-error-${index}` }, error)))
        : null,
    ]);
  },
});

const NoteField = defineComponent({
  props: { form: Object },
  setup(props) {
    return () => h('div', [
      h('label', { class: 'mb-1 block text-sm text-slate-300' }, 'Note'),
      h('textarea', {
        value: props.form.note,
        rows: 4,
        class: 'w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-3 text-slate-100',
        onInput: (event) => { props.form.note = event.target.value; },
      }),
      props.form.errors.note ? h('div', { class: 'mt-1 text-xs text-rose-300' }, props.form.errors.note) : null,
    ]);
  },
});
</script>
