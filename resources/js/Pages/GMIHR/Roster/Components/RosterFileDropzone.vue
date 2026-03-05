<template>
  <div
    class="border-2 border-dashed rounded-lg p-6 text-center transition"
    :class="isDragging ? 'border-indigo-400 bg-indigo-500/10' : 'border-slate-600 bg-slate-900/40'"
    role="button"
    tabindex="0"
    @dragenter.prevent.stop="onDragEnter"
    @dragover.prevent="isDragging = true"
    @dragleave.prevent.stop="onDragLeave"
    @drop.prevent.stop="onDrop"
    @click="openFilePicker"
    @keydown.enter.prevent="openFilePicker"
    @keydown.space.prevent="openFilePicker"
  >
    <input ref="fileInput" type="file" class="hidden" accept=".csv,.txt,.xlsx,.xls" @change="onFilePicked" />
    <p class="text-slate-300">
      Drag & drop file CSV/Excel di sini atau
      <button type="button" class="text-indigo-400 underline" @click.stop="openFilePicker">pilih file</button>
    </p>
    <p class="text-xs text-slate-400 mt-2">Untuk CSV delimiter didukung: <code>,</code> <code>;</code> <code>|</code></p>
    <p v-if="fileName" class="text-sm text-emerald-300 mt-2">File: {{ fileName }}</p>
  </div>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
  fileName: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['file-selected']);
const fileInput = ref(null);
const isDragging = ref(false);
const dragDepth = ref(0);

function onFilePicked(event) {
  const file = event.target.files?.[0] || null;
  emit('file-selected', file);
}

function openFilePicker() {
  fileInput.value?.click();
}

function onDrop(event) {
  dragDepth.value = 0;
  isDragging.value = false;
  const file = event.dataTransfer?.files?.[0] || null;
  if (!file) return;
  emit('file-selected', file);
}

function onDragEnter() {
  dragDepth.value += 1;
  isDragging.value = true;
}

function onDragLeave() {
  dragDepth.value = Math.max(0, dragDepth.value - 1);
  if (dragDepth.value === 0) {
    isDragging.value = false;
  }
}
</script>
