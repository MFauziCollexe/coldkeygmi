<template>
  <div :class="containerClass">
    <div class="flex items-center gap-3 p-4 bg-slate-800/50 rounded-lg border border-slate-700">
      <input 
        :checked="modelValue"
        type="checkbox"
        :required="required"
        class="w-5 h-5 accent-indigo-600 bg-slate-900 border-slate-600 rounded cursor-pointer"
        @change="$emit('update:modelValue', $event.target.checked)"
      />
      <label class="text-sm text-slate-300 cursor-pointer">
        <slot />
      </label>
    </div>
  </div>
</template>

<script setup>
defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  required: {
    type: Boolean,
    default: false,
  },
  containerClass: {
    type: String,
    default: '',
  },
});

defineEmits(['update:modelValue']);
</script>
