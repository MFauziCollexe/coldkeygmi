<template>
  <div class="p-4 bg-slate-900/40 rounded">
    <div class="text-slate-300">{{ label }}</div>
    <div :class="`text-2xl font-bold mt-2 ${textColor}`">{{ value }}</div>
  </div>
</template>

<script setup>
defineProps({
  label: {
    type: String,
    required: true,
  },
  value: {
    type: [String, Number],
    required: true,
  },
  textColor: {
    type: String,
    default: 'text-white',
  },
});
</script>
