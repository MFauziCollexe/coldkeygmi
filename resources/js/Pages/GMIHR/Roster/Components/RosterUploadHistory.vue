<template>
  <div class="bg-slate-800 rounded-lg p-4 border border-slate-700">
    <h3 class="font-semibold mb-3">Riwayat Upload Terbaru</h3>
    <div v-if="!items || items.length === 0" class="text-slate-400 text-sm">Belum ada upload.</div>
    <div v-else class="overflow-auto">
      <table class="w-full text-sm">
        <thead class="text-slate-400 border-b border-slate-700">
          <tr>
            <th class="text-left py-2 pr-3">Periode</th>
            <th class="text-left py-2 pr-3">File</th>
            <th class="text-left py-2 pr-3">Delimiter</th>
            <th class="text-left py-2 pr-3">Rows</th>
            <th class="text-left py-2">Waktu</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="batch in items" :key="batch.id" class="border-b border-slate-700/60">
            <td class="py-2 pr-3">{{ batch.month }}/{{ batch.year }}</td>
            <td class="py-2 pr-3">{{ batch.filename }}</td>
            <td class="py-2 pr-3">{{ batch.delimiter }}</td>
            <td class="py-2 pr-3">{{ batch.saved_rows }}/{{ batch.total_rows }}</td>
            <td class="py-2">{{ formatDate(batch.created_at) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
defineProps({
  items: {
    type: Array,
    default: () => [],
  },
});

function formatDate(value) {
  if (!value) return '-';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '-';
  return date.toLocaleString();
}
</script>
