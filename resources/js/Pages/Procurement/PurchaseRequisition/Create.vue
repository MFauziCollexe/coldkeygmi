<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mx-auto max-w-7xl space-y-4">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 class="text-2xl font-bold">Create Purchase Requisition</h2>
            <p class="text-sm text-slate-400">Buat PR dengan detail item, harga, dan required date per baris.</p>
          </div>
          <Link href="/gmisl/procurement/purchase-requisition" class="text-sm text-indigo-400">Back to list</Link>
        </div>

        <div class="rounded border border-sky-700 bg-sky-700/10 px-4 py-3 text-sm text-sky-200">
          PR disimpan terpisah dari master item. Data item pada PR akan menjadi snapshot histori walaupun master item berubah di kemudian hari.
        </div>

        <form @submit.prevent="submit" class="space-y-4 rounded bg-slate-800 p-4 md:p-6">
          <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <div class="space-y-4">
              <FieldReadOnly label="No PR" :value="defaults.pr_number || ''" />

              <div class="relative pt-0">
                <label class="absolute left-3 -top-0.5 z-10 -translate-y-1/2 bg-slate-800 px-1 text-sm text-slate-300">PR Date</label>
                <EnhancedDatePicker
                  :model-value="defaults.pr_date || ''"
                  disabled
                  placeholder="dd/mm/yyyy"
                  input-class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-3 text-slate-100 placeholder-transparent"
                />
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

          <PurchaseRequisitionItemEditor :form="form" :uom-options="uomOptions" :master-items="masterItems" :minimum-required-date="minimumRequiredDate" />
          <NoteField :form="form" />
          <AttachmentUploader :form="form" />

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
import PurchaseRequisitionItemEditor from './Partials/PurchaseRequisitionItemEditor.vue';
import { defineComponent, h, ref, computed } from 'vue';

const props = defineProps({
  defaults: { type: Object, default: () => ({}) },
  uomOptions: { type: Array, default: () => [] },
  masterItems: { type: Array, default: () => [] },
  minimumRequiredDate: { type: String, default: '' },
  currentUser: { type: Object, default: () => ({}) },
});

const form = useForm({
  priority: String(props.defaults?.priority || 'medium'),
  department_id: props.defaults?.department_id || '',
  note: '',
  items: [],
  attachments: [],
});

function submit() {
  form.transform((data) => buildPayload(data));

  form.post('/gmisl/procurement/purchase-requisition', {
    preserveScroll: true,
    onFinish: () => {
      form.transform((data) => data);
    },
  });
}

function buildPayload(data) {
  const payload = new FormData();

  payload.append('priority', data.priority || '');
  payload.append('department_id', data.department_id || '');
  payload.append('note', data.note || '');

  data.items.forEach((item, index) => {
    appendItem(payload, item, index);
  });

  data.attachments.forEach((file) => {
    payload.append('attachments[]', file);
  });

  return payload;
}

function appendItem(payload, item, index) {
  const fields = [
    'procurement_master_item_id',
    'item_code',
    'item_name',
    'description_of_goods',
    'specification',
    'unit',
    'quantity',
    'required_date',
    'price',
  ];

  fields.forEach((field) => {
    payload.append(`items[${index}][${field}]`, item[field] ?? '');
  });
}

const FieldReadOnly = defineComponent({
  props: { label: String, value: String },
  setup(readonlyProps, { slots }) {
    return () => h('div', [
      h('label', { class: 'mb-1 block text-sm text-slate-300' }, readonlyProps.label || ''),
      h('input', { value: readonlyProps.value || '', disabled: true, class: 'w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-3 text-slate-100 disabled:opacity-100' }),
      slots.error?.(),
    ]);
  },
});

const AttachmentUploader = defineComponent({
  props: { form: Object },
  setup(localProps) {
    const fileInput = ref(null);
    const dragActive = ref(false);
    const attachmentErrorList = computed(() =>
      Object.entries(localProps.form.errors)
        .filter(([key]) => key === 'attachments' || key.startsWith('attachments.'))
        .map(([, value]) => value)
    );
    function clickFileInput() {
      if (fileInput.value) fileInput.value.click();
    }
    function handleFileUpload(event) {
      const files = Array.from(event?.target?.files || []);
      localProps.form.attachments = [...localProps.form.attachments, ...files];
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
      localProps.form.attachments = [...localProps.form.attachments, ...files];
    }
    function removeAttachment(index) {
      localProps.form.attachments.splice(index, 1);
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
      localProps.form.attachments.length
        ? h('div', { class: 'space-y-2' }, localProps.form.attachments.map((file, index) =>
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
  setup(localProps) {
    return () => h('div', [
      h('label', { class: 'mb-1 block text-sm text-slate-300' }, 'Note'),
      h('textarea', {
        value: localProps.form.note,
        rows: 4,
        class: 'w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-3 text-slate-100',
        onInput: (event) => { localProps.form.note = event.target.value; },
      }),
      localProps.form.errors.note ? h('div', { class: 'mt-1 text-xs text-rose-300' }, localProps.form.errors.note) : null,
    ]);
  },
});
</script>
