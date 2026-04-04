<template>
  <div class="rounded-lg border border-slate-700 bg-gradient-to-br from-slate-800/60 to-slate-800/30 p-4 shadow-lg md:p-6">
    <div class="flex items-center justify-between">
      <div>
        <div class="text-sm text-slate-400">{{ title }}</div>
        <div class="mt-3 text-3xl font-extrabold md:text-4xl">{{ value }}</div>
      </div>
      <div class="flex h-11 w-11 items-center justify-center rounded-lg text-sm text-white md:h-12 md:w-12" :class="`bg-${bgColor}-700`">
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
