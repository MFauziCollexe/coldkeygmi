<template>
  <section :class="{ 'pb-8 border-b border-slate-700': divider }">
    <h2 class="text-2xl font-bold text-white mb-4">
      {{ number }}. {{ title }}
    </h2>
    <slot />
  </section>
</template>

<script setup>
defineProps({
  number: {
    type: [String, Number],
    required: true,
  },
  title: {
    type: String,
    required: true,
  },
  divider: {
    type: Boolean,
    default: true,
  },
});
</script>
