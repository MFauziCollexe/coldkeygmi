<template>
  <div :class="containerClass">
    <div class="relative group">
      <select
      class="w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 text-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition cursor-pointer"
      :value="modelValue"
      :required="required"
      :disabled="disabled"
      @change="$emit('update:modelValue', $event.target.value)"
    >
      <option value="" disabled>{{ placeholder }}</option>
      <option v-for="option in options" :key="option" :value="option">
        {{ option }}
      </option>
    </select>
      <label
        :class="[
          'pointer-events-none absolute left-3 z-10 transition-all',
          hasValue
            ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
            : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2',
          'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
        ]"
      >
        {{ label }}
        <span v-if="required" class="text-red-500">*</span>
      </label>
    </div>
    <p v-if="error" class="mt-2 text-sm text-red-500">{{ error }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: '',
  },
  label: {
    type: String,
    required: true,
  },
  placeholder: {
    type: String,
    default: 'Select an option',
  },
  options: {
    type: Array,
    default: () => [],
  },
  required: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: '',
  },
  containerClass: {
    type: String,
    default: '',
  },
});

const hasValue = computed(() => props.modelValue !== '' && props.modelValue !== null && props.modelValue !== undefined);

defineEmits(['update:modelValue']);
</script>
