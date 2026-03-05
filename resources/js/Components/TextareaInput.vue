<template>
  <div :class="containerClass">
    <div class="relative">
      <textarea
      class="peer w-full px-3 pt-6 pb-2 rounded-lg bg-slate-800 border border-slate-700 text-white placeholder-transparent focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition resize-none"
      :value="modelValue"
      :placeholder="placeholder || ' '"
      :rows="rows"
      :required="required"
      @input="$emit('update:modelValue', $event.target.value)"
    />
      <label
        class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-4 peer-placeholder-shown:translate-y-0 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1"
      >
        {{ label }}
        <span v-if="required" class="text-red-500">*</span>
      </label>
    </div>
    <p v-if="error" class="mt-2 text-sm text-red-500">{{ error }}</p>
  </div>
</template>

<script setup>
defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  label: {
    type: String,
    required: true,
  },
  placeholder: {
    type: String,
    default: '',
  },
  rows: {
    type: Number,
    default: 4,
  },
  required: {
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

defineEmits(['update:modelValue']);
</script>
