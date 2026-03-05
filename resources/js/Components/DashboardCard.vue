<template>
  <div class="p-6 rounded-lg bg-gradient-to-br from-slate-800/60 to-slate-800/30 border border-slate-700 shadow-lg">
    <div class="flex items-center justify-between">
      <div>
        <div class="text-slate-400 text-sm">{{ title }}</div>
        <div class="text-4xl font-extrabold mt-3">{{ value }}</div>
      </div>
      <div class="w-12 h-12 rounded-lg flex items-center justify-center text-white" :class="`bg-${bgColor}-700`">
        {{ icon }}
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
  value: {
    type: [String, Number],
    required: true,
  },
  icon: {
    type: String,
    default: '📊',
  },
  bgColor: {
    type: String,
    default: 'indigo',
  },
});
</script>
