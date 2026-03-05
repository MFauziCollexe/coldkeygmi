<template>
  <div>
    <h2 class="text-lg font-semibold text-white mb-6 pb-4 border-b border-slate-700">
      {{ title }}
    </h2>
    <slot />
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
});
</script>
