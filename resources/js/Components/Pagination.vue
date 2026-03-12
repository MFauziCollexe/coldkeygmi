<template>
  <div v-if="lastPage > 1" class="flex justify-center items-center gap-2">
    <button
      type="button"
      class="px-3 py-1 rounded bg-slate-700 text-slate-200 disabled:opacity-50"
      :disabled="currentPage <= 1"
      @click="onPageChange(currentPage - 1)"
    >
      Prev
    </button>

    <button
      v-for="(item, index) in pageItems"
      :key="`${item}-${index}`"
      type="button"
      class="px-3 py-1 rounded disabled:opacity-50"
      :class="item === currentPage ? 'bg-indigo-600 text-white' : 'bg-slate-700 text-slate-200 hover:bg-slate-600'"
      :disabled="item === '...'"
      @click="item !== '...' && onPageChange(item)"
    >
      {{ item }}
    </button>

    <button
      type="button"
      class="px-3 py-1 rounded bg-slate-700 text-slate-200 disabled:opacity-50"
      :disabled="currentPage >= lastPage"
      @click="onPageChange(currentPage + 1)"
    >
      Next
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  paginator: { type: Object, required: true },
  onPageChange: { type: Function, required: true },
  siblings: { type: Number, default: 2 },
});

const currentPage = computed(() => Number(props.paginator?.current_page || 1));
const lastPage = computed(() => Number(props.paginator?.last_page || 1));

const pageItems = computed(() => {
  const current = currentPage.value;
  const last = lastPage.value;
  const siblings = Math.max(0, Number(props.siblings || 0));

  if (last <= 1) return [];

  const items = [];
  const add = (value) => items.push(value);

  add(1);

  const start = Math.max(2, current - siblings);
  const end = Math.min(last - 1, current + siblings);

  if (start > 2) add('...');
  for (let p = start; p <= end; p++) add(p);
  if (end < last - 1) add('...');

  if (last > 1) add(last);

  return items;
});
</script>
