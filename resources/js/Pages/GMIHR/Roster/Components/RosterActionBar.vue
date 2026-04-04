<template>
  <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-end">
    <div class="relative min-w-0 group sm:min-w-[180px]">
      <SearchableSelect
        :model-value="templateType"
        :options="templateOptions"
        option-value="value"
        option-label="label"
        placeholder=" "
        empty-label="Template"
        input-class="w-full pl-3 pr-10 pt-5 pb-2 !bg-slate-800 !border-slate-700 rounded-lg text-slate-100"
        button-class="border-0 border-l !border-slate-700 rounded-r-lg !bg-slate-800 text-slate-100"
        @update:modelValue="emit('update:templateType', $event)"
      />
      <label
        :class="[
          'pointer-events-none absolute left-3 z-10 transition-all',
          (templateType
            ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
            : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
          'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
        ]"
      >
        Template
      </label>
    </div>

    <button class="h-[52px] w-full rounded bg-slate-600 px-4 text-white sm:w-auto" @click="emit('download-template')">
      Download Template
    </button>

    <button
      class="h-[52px] w-full rounded bg-indigo-600 px-4 text-white disabled:opacity-50 sm:w-auto"
      :disabled="isPreviewLoading || !hasFile"
      @click="emit('preview')"
    >
      {{ isPreviewLoading ? 'Previewing...' : 'Preview' }}
    </button>

    <button
      class="h-[52px] w-full rounded bg-emerald-600 px-4 text-white disabled:opacity-50 sm:w-auto"
      :disabled="isUploadLoading || !canUpload"
      @click="emit('upload')"
    >
      {{ isUploadLoading ? 'Uploading...' : 'Upload' }}
    </button>

    <span v-if="delimiter" class="text-xs text-slate-300 sm:self-center">
      Delimiter terdeteksi: <code>{{ delimiter }}</code>
    </span>
  </div>
</template>

<script setup>
import SearchableSelect from '@/Components/SearchableSelect.vue';

defineProps({
  templateType: {
    type: String,
    default: '',
  },
  isPreviewLoading: {
    type: Boolean,
    default: false,
  },
  isUploadLoading: {
    type: Boolean,
    default: false,
  },
  hasFile: {
    type: Boolean,
    default: false,
  },
  canUpload: {
    type: Boolean,
    default: false,
  },
  delimiter: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['update:templateType', 'download-template', 'preview', 'upload']);
const templateOptions = [
  { value: 'inventory', label: 'Inventory' },
  { value: 'risk_control', label: 'Risk Control' },
  { value: 'admin_loket', label: 'Admin Loket' },
  { value: 'maintanance', label: 'Maintanance' },
];
</script>
