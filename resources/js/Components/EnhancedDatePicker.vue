<template>
  <input
    ref="inputRef"
    class="block w-full"
    type="text"
    :placeholder="placeholder"
    :disabled="disabled"
    :class="mergedInputClass"
  />
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import flatpickr from 'flatpickr';
import { Indonesian } from 'flatpickr/dist/l10n/id.js';
import 'flatpickr/dist/flatpickr.css';

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: 'dd/mm/yyyy',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  inputClass: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['update:modelValue']);
const inputRef = ref(null);
let picker = null;
const mergedInputClass = computed(() =>
  [
    'w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-slate-500 text-slate-100 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition',
    props.inputClass,
  ]
    .filter(Boolean)
    .join(' ')
);

onMounted(() => {
  flatpickr.localize(Indonesian);
  picker = flatpickr(inputRef.value, {
    dateFormat: 'Y-m-d',
    altInput: true,
    altInputClass: mergedInputClass.value,
    altFormat: 'd/m/Y',
    allowInput: true,
    locale: 'id',
    defaultDate: props.modelValue || null,
    disableMobile: true,
    onReady: (selectedDates, dateStr, instance) => {
      if (instance.altInput) {
        instance.altInput.placeholder = props.placeholder || '';
        instance.altInput.disabled = props.disabled;
        instance.altInput.style.width = '100%';
        instance.altInput.style.display = 'block';
      }
    },
    onChange: (selectedDates, dateStr) => {
      emit('update:modelValue', dateStr || '');
    },
  });
});

watch(
  () => props.modelValue,
  (newValue) => {
    if (!picker) return;
    const current = picker.input?.value || '';
    if ((newValue || '') === current) return;
    picker.setDate(newValue || null, false, 'Y-m-d');
  }
);

watch(
  () => props.disabled,
  (isDisabled) => {
    if (!picker?.altInput) return;
    picker.altInput.disabled = isDisabled;
  }
);

onBeforeUnmount(() => {
  if (picker) {
    picker.destroy();
    picker = null;
  }
});
</script>

<style scoped>
:deep(.flatpickr-wrapper) {
  display: block;
  width: 100%;
}

:deep(.flatpickr-wrapper > .flatpickr-input),
:deep(.flatpickr-wrapper > input) {
  width: 100%;
  display: block;
}

:deep(.flatpickr-input[readonly]) {
  color: #f1f5f9;
}

:deep(.flatpickr-calendar) {
  background: #0f172a;
  border: 1px solid #334155;
  box-shadow: 0 10px 25px rgba(2, 6, 23, 0.45);
}

:deep(.flatpickr-months .flatpickr-month),
:deep(.flatpickr-weekdays),
:deep(span.flatpickr-weekday),
:deep(.flatpickr-current-month .flatpickr-monthDropdown-months),
:deep(.flatpickr-current-month input.cur-year) {
  background: #0f172a;
  color: #e2e8f0;
}

:deep(.flatpickr-day) {
  color: #cbd5e1;
}

:deep(.flatpickr-day:hover),
:deep(.flatpickr-day:focus) {
  background: #1e293b;
  border-color: #334155;
}

:deep(.flatpickr-day.today) {
  border-color: #22c55e;
}

:deep(.flatpickr-day.selected),
:deep(.flatpickr-day.startRange),
:deep(.flatpickr-day.endRange) {
  background: #2563eb;
  border-color: #2563eb;
  color: #fff;
}

:deep(.flatpickr-day.prevMonthDay),
:deep(.flatpickr-day.nextMonthDay) {
  color: #64748b;
}

:deep(.flatpickr-months .flatpickr-prev-month svg),
:deep(.flatpickr-months .flatpickr-next-month svg) {
  fill: #e2e8f0;
}
</style>
