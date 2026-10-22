<template>
  <div ref="rootRef" class="relative min-w-0 flex-1">
    <div
      :class="[
        'flex min-h-[42px] flex-wrap items-center gap-1.5 rounded border px-2 py-1.5',
        variant === 'light' ? 'border-slate-300 bg-white' : 'border-slate-700 bg-slate-800',
        open ? 'ring-1 ring-sky-500' : '',
      ]"
      @click="focusInput"
    >
      <span
        v-for="option in selectedOptions"
        :key="String(getOptionValue(option))"
        class="inline-flex max-w-full items-center gap-1 rounded bg-slate-200 px-2 py-1 text-xs text-slate-800"
      >
        <span class="truncate">{{ getOptionLabel(option) }}</span>
        <button
          type="button"
          class="shrink-0 text-slate-500 hover:text-slate-900"
          :aria-label="`Hapus ${getOptionLabel(option)}`"
          @click.stop="removeOption(option)"
        >
          &times;
        </button>
      </span>
      <input
        ref="inputRef"
        :id="id || undefined"
        v-model="query"
        type="text"
        :placeholder="selectedOptions.length ? '' : placeholder"
        :disabled="disabled"
        class="min-w-[120px] flex-1 border-0 bg-transparent px-1 py-1 text-sm text-slate-900 outline-none placeholder:text-slate-500 focus:ring-0"
        @focus="open = true"
        @keydown.escape="open = false"
        @keydown.backspace="removeLastOption"
      />
      <button
        v-if="selectedOptions.length"
        type="button"
        class="px-1 text-slate-500 hover:text-slate-900"
        aria-label="Hapus semua pilihan"
        @click.stop="clearSelection"
      >
        &times;
      </button>
      <button
        type="button"
        class="shrink-0 px-1 text-slate-500"
        :aria-expanded="open"
        aria-label="Tampilkan pilihan product"
        @click.stop="toggleOpen"
      >
        &#9662;
      </button>
    </div>

    <div
      v-if="open"
      ref="menuRef"
      :class="[
        'absolute left-0 right-0 z-30 max-h-56 overflow-auto rounded border shadow-lg',
        variant === 'light' ? 'border-slate-300 bg-white text-slate-900' : 'border-slate-700 bg-slate-800 text-slate-100',
        openUp ? 'bottom-full mb-1' : 'top-full mt-1',
      ]"
    >
      <button
        v-for="option in filteredOptions"
        :key="String(getOptionValue(option))"
        type="button"
        class="flex w-full items-center justify-between gap-3 border-b border-slate-200 px-3 py-2 text-left text-sm last:border-b-0 hover:bg-sky-50"
        @click="toggleOption(option)"
      >
        <span>{{ getOptionLabel(option) }}</span>
        <span class="text-sky-700">{{ isSelected(option) ? '✓' : '' }}</span>
      </button>
      <div v-if="!filteredOptions.length" class="px-3 py-2 text-xs text-slate-500">
        Tidak ada product yang cocok.
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
  id: { type: String, default: '' },
  modelValue: { type: Array, default: () => [] },
  options: { type: Array, default: () => [] },
  optionValue: { type: String, default: 'id' },
  optionLabel: { type: String, default: 'name' },
  placeholder: { type: String, default: 'Cari product...' },
  variant: { type: String, default: 'dark' },
  disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);
const rootRef = ref(null);
const inputRef = ref(null);
const menuRef = ref(null);
const query = ref('');
const open = ref(false);
const openUp = ref(false);

const selectedOptions = computed(() => {
  const selected = new Set((props.modelValue || []).map(String));
  return (props.options || []).filter((option) => selected.has(String(getOptionValue(option))));
});

const filteredOptions = computed(() => {
  const search = query.value.trim().toLowerCase();
  const options = props.options || [];
  const filtered = search
    ? options.filter((option) => String(getOptionLabel(option) || '').toLowerCase().includes(search))
    : options;
  return filtered.slice(0, 200);
});

function getOptionValue(option) {
  return option !== null && typeof option === 'object' ? option?.[props.optionValue] ?? '' : option ?? '';
}

function getOptionLabel(option) {
  return option !== null && typeof option === 'object' ? option?.[props.optionLabel] ?? '' : option ?? '';
}

function isSelected(option) {
  return (props.modelValue || []).some((value) => String(value) === String(getOptionValue(option)));
}

function toggleOption(option) {
  const value = getOptionValue(option);
  const current = [...(props.modelValue || [])];
  const selected = current.some((item) => String(item) === String(value));
  const allItemsOption = String(value) === '0';

  if (selected) {
    emit('update:modelValue', current.filter((item) => String(item) !== String(value)));
  } else if (allItemsOption) {
    emit('update:modelValue', [value]);
  } else {
    emit('update:modelValue', [...current.filter((item) => String(item) !== '0'), value]);
  }

  query.value = '';
  nextTick(() => inputRef.value?.focus());
}

function removeOption(option) {
  const value = String(getOptionValue(option));
  emit('update:modelValue', (props.modelValue || []).filter((item) => String(item) !== value));
}

function removeLastOption() {
  if (query.value || !(props.modelValue || []).length) return;
  emit('update:modelValue', (props.modelValue || []).slice(0, -1));
}

function clearSelection() {
  emit('update:modelValue', []);
  query.value = '';
}

function focusInput() {
  inputRef.value?.focus();
}

function toggleOpen() {
  if (props.disabled) return;
  open.value = !open.value;
  if (open.value) nextTick(() => inputRef.value?.focus());
}

function updateDropdownDirection() {
  if (!rootRef.value) return;
  const rect = rootRef.value.getBoundingClientRect();
  const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
  openUp.value = viewportHeight - rect.bottom < 230 && rect.top > viewportHeight - rect.bottom;
}

function handleOutsideClick(event) {
  if (rootRef.value && !rootRef.value.contains(event.target)) open.value = false;
}

watch(open, async (isOpen) => {
  if (!isOpen) return;
  await nextTick();
  updateDropdownDirection();
});

onMounted(() => {
  document.addEventListener('click', handleOutsideClick);
  window.addEventListener('resize', updateDropdownDirection);
  window.addEventListener('scroll', updateDropdownDirection, true);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleOutsideClick);
  window.removeEventListener('resize', updateDropdownDirection);
  window.removeEventListener('scroll', updateDropdownDirection, true);
});
</script>
