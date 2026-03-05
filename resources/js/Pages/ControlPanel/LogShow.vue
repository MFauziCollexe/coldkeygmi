<template>
  <AppLayout>
    <div class="p-6 max-w-3xl">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold">Log Details</h2>
        <Link href="/control-panel/logs" class="text-indigo-400">Back to logs</Link>
      </div>

      <div class="bg-slate-800 rounded p-6 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <div class="text-sm text-slate-400">ID</div>
            <div class="font-semibold">{{ log.id }}</div>
          </div>
          <div>
            <div class="text-sm text-slate-400">Action</div>
            <div class="font-semibold">{{ log.action || '-' }}</div>
          </div>
          <div>
            <div class="text-sm text-slate-400">Table</div>
            <div class="font-semibold">{{ log.table_name || '-' }}</div>
          </div>
          <div>
            <div class="text-sm text-slate-400">Record ID</div>
            <div class="font-semibold">{{ log.record_id || '-' }}</div>
          </div>
          <div>
            <div class="text-sm text-slate-400">User</div>
            <div class="font-semibold">{{ log.user_email || '-' }}</div>
          </div>
          <div>
            <div class="text-sm text-slate-400">IP Address</div>
            <div class="font-semibold">{{ log.ip_address || '-' }}</div>
          </div>
          <div class="md:col-span-2">
            <div class="text-sm text-slate-400">Description</div>
            <div class="font-semibold">{{ log.description || '-' }}</div>
          </div>
          <div class="md:col-span-2">
            <div class="text-sm text-slate-400">Created</div>
            <div class="font-semibold">{{ formatDate(log.created_date) }}</div>
          </div>
          <div v-if="log.old_values" class="md:col-span-2">
            <div class="text-sm text-slate-400">Old Values</div>
            <pre class="bg-slate-900 p-3 rounded text-xs overflow-auto">{{ pretty(log.old_values) }}</pre>
          </div>
          <div v-if="log.new_values" class="md:col-span-2">
            <div class="text-sm text-slate-400">New Values</div>
            <pre class="bg-slate-900 p-3 rounded text-xs overflow-auto">{{ pretty(log.new_values) }}</pre>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  log: {
    type: Object,
    required: true,
  },
});

function formatDate(value) {
  if (!value) return '-';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '-';
  return date.toLocaleString();
}

function pretty(value) {
  if (typeof value === 'string') return value;
  try {
    return JSON.stringify(value, null, 2);
  } catch (error) {
    return String(value ?? '');
  }
}
</script>
