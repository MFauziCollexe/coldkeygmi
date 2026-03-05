<template>
  <div :class="containerClass">
    <label class="block text-sm font-semibold text-slate-300 mb-3">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    <div class="relative">
      <input 
        :value="modelValue"
        :type="isVisible ? 'text' : 'password'"
        :placeholder="placeholder"
        :required="required"
        class="w-full bg-slate-800 border border-slate-700 rounded-lg px-4 py-3 text-white placeholder-slate-500 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition pr-10"
        @input="$emit('update:modelValue', $event.target.value)"
      />
      <button 
        type="button"
        @click="isVisible = !isVisible"
        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400 hover:text-slate-300"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        </svg>
      </button>
    </div>
    <p v-if="error" class="mt-2 text-sm text-red-500">{{ error }}</p>
  </div>
</template>

<script setup>
import { ref } from 'vue';

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

const isVisible = ref(false);
</script>
