<template>
  <div class="rounded bg-slate-900/40 p-4">
    <div class="text-sm text-slate-300">{{ label }}</div>
    <div :class="`mt-2 text-xl font-bold md:text-2xl ${textColor}`">{{ value }}</div>
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
