<template>
  <div ref="rootRef" class="relative">
    <input
      v-model="query"
      type="text"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      :class="[
        'w-full pl-3 pr-10 py-2 bg-slate-800 text-slate-100 border border-slate-700 focus:outline-none',
        open ? (openUp ? 'rounded-b rounded-t-none' : 'rounded-t rounded-b-none') : 'rounded',
        inputClass,
      ]"
      @focus="open = true"
      @input="onInput"
    />
    <button
      type="button"
      :disabled="disabled"
      :class="[
        'absolute right-0 top-0 h-full w-10 border-l border-r border-y border-slate-700 text-sm leading-none flex items-center justify-center bg-slate-800 text-slate-100',
        open ? (openUp ? 'rounded-br-none rounded-tr' : 'rounded-tr-none rounded-br') : 'rounded-r',
        buttonClass,
      ]"
      @click="toggleOpen"
    >
      &#9662;
    </button>

    <div
      ref="menuRef"
      v-if="open"
      :class="[
        'absolute z-20 left-0 right-0 max-h-52 overflow-auto border border-slate-700 bg-slate-800',
        openUp
          ? 'bottom-full rounded-t border-b-0'
          : 'top-full rounded-b border-t-0',
      ]"
    >
      <button
        type="button"
        class="w-full text-left px-3 py-2 text-sm hover:bg-slate-800 border-b border-slate-800"
        @click="clearSelection"
      >
        {{ emptyLabel }}
      </button>
      <button
        v-for="option in filteredOptions"
        :key="String(getOptionValue(option))"
        type="button"
        class="w-full text-left px-3 py-2 text-sm hover:bg-slate-800 border-b border-slate-800 last:border-b-0"
        @click="selectOption(option)"
      >
        {{ getOptionLabel(option) }}
      </button>
      <div v-if="!filteredOptions.length" class="px-3 py-2 text-xs text-slate-400">
        Tidak ada data yang cocok.
      </div>
      <div
        v-else-if="matchCount > filteredOptions.length"
        class="px-3 py-2 text-[11px] text-slate-400 border-t border-slate-700"
      >
        Menampilkan {{ filteredOptions.length }} dari {{ matchCount }} data. Ketik untuk mempersempit.
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
  modelValue: {
    type: [String, Number, null],
    default: '',
  },
  options: {
    type: Array,
    default: () => [],
  },
  optionValue: {
    type: String,
    default: 'id',
  },
  optionLabel: {
    type: String,
    default: 'name',
  },
  placeholder: {
    type: String,
    default: 'Pilih data',
  },
  emptyLabel: {
    type: String,
    default: 'Pilih data',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  required: {
    type: Boolean,
    default: false,
  },
  inputClass: {
    type: String,
    default: '',
  },
  buttonClass: {
    type: String,
    default: '',
  },
  // Prevent rendering huge lists in the DOM (can make click handlers slow).
  maxOptions: {
    type: Number,
    default: 200,
  },
});

const emit = defineEmits(['update:modelValue']);

const rootRef = ref(null);
const menuRef = ref(null);
const query = ref('');
const open = ref(false);
const openUp = ref(false);
let rafId = null;

const filteredOptions = computed(() => {
  const q = String(query.value || '').trim().toLowerCase();
  const list = Array.isArray(props.options) ? props.options : [];
  const filtered = !q
    ? list
    : list.filter((option) => String(getOptionLabel(option) || '').toLowerCase().includes(q));

  const limit = Number.isFinite(props.maxOptions) ? Math.max(1, Math.floor(props.maxOptions)) : 200;
  return filtered.slice(0, limit);
});

const matchCount = computed(() => {
  const q = String(query.value || '').trim().toLowerCase();
  const list = Array.isArray(props.options) ? props.options : [];
  if (!q) return list.length;
  return list.reduce((count, option) => {
    return String(getOptionLabel(option) || '').toLowerCase().includes(q) ? count + 1 : count;
  }, 0);
});

function getOptionValue(option) {
  if (option !== null && typeof option === 'object') {
    return option?.[props.optionValue] ?? '';
  }
  return option ?? '';
}

function getOptionLabel(option) {
  if (option !== null && typeof option === 'object') {
    return option?.[props.optionLabel] ?? '';
  }
  return option ?? '';
}

function syncQueryFromModel() {
  const selected = props.options.find((option) => String(getOptionValue(option)) === String(props.modelValue));
  query.value = selected ? String(getOptionLabel(selected) || '') : '';
}

function onInput() {
  open.value = true;
  const q = String(query.value || '').trim().toLowerCase();
  if (!q) {
    emit('update:modelValue', '');
    return;
  }

  const exact = props.options.find((option) => String(getOptionLabel(option) || '').trim().toLowerCase() === q);
  emit('update:modelValue', exact ? getOptionValue(exact) : '');
}

function selectOption(option) {
  emit('update:modelValue', getOptionValue(option));
  query.value = String(getOptionLabel(option) || '');
  open.value = false;
}

function clearSelection() {
  emit('update:modelValue', '');
  query.value = '';
  open.value = false;
}

function toggleOpen() {
  if (props.disabled) return;
  open.value = !open.value;
}

function updateDropdownDirection() {
  const root = rootRef.value;
  if (!root) return;

  const rect = root.getBoundingClientRect();
  const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
  const spaceBelow = viewportHeight - rect.bottom;
  const spaceAbove = rect.top;

  // Default dropdown max height is 13rem (~208px); add some buffer.
  const fallbackMenuHeight = 220;
  const actualMenuHeight = menuRef.value ? Math.min(menuRef.value.scrollHeight, fallbackMenuHeight) : fallbackMenuHeight;
  openUp.value = spaceBelow < actualMenuHeight && spaceAbove > spaceBelow;
}

function scheduleDropdownDirectionUpdate() {
  if (rafId !== null) {
    cancelAnimationFrame(rafId);
  }
  rafId = requestAnimationFrame(() => {
    rafId = null;
    updateDropdownDirection();
  });
}

function handleOutsideClick(event) {
  const root = rootRef.value;
  if (!root) return;
  if (!root.contains(event.target)) {
    open.value = false;
  }
}

watch(() => props.modelValue, syncQueryFromModel, { immediate: true });
watch(() => props.options, syncQueryFromModel, { deep: true });
watch(open, async (isOpen) => {
  if (!isOpen) return;
  await nextTick();
  scheduleDropdownDirectionUpdate();
});

onMounted(() => {
  document.addEventListener('click', handleOutsideClick);
  window.addEventListener('resize', scheduleDropdownDirectionUpdate);
  window.addEventListener('scroll', scheduleDropdownDirectionUpdate, true);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleOutsideClick);
  window.removeEventListener('resize', scheduleDropdownDirectionUpdate);
  window.removeEventListener('scroll', scheduleDropdownDirectionUpdate, true);
  if (rafId !== null) {
    cancelAnimationFrame(rafId);
    rafId = null;
  }
});
</script>
