<template>
  <div class="mb-12">
    <Link href="/" class="inline-flex items-center text-indigo-400 hover:text-indigo-300 text-sm font-semibold mb-6">
      <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
      </svg>
      {{ backText }}
    </Link>
    <h1 class="text-4xl font-bold text-white mb-3">
      {{ title }}
    </h1>
    <p class="text-slate-400 text-sm">
      {{ lastUpdated }}
    </p>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
  title: {
    type: String,
    required: true,
  },
  lastUpdated: {
    type: String,
    default: 'Last updated: February 19, 2026',
  },
  backText: {
    type: String,
    default: 'Back to Home',
  },
});
</script>
