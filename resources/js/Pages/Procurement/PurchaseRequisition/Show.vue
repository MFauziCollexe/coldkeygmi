<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mx-auto max-w-6xl space-y-4">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 class="text-2xl font-bold">Detail Purchase Requisition</h2>
            <p class="text-sm text-slate-400">Informasi detail purchase requisition.</p>
          </div>
          <Link href="/gmisl/procurement/purchase-requisition" class="text-sm text-indigo-400">Back to list</Link>
        </div>

        <form class="space-y-4 rounded bg-slate-800 p-4 md:p-6">
          <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <div class="space-y-4">
              <div>
                <label class="mb-1 block text-sm text-slate-300">PR Number</label>
                <input :value="purchaseRequisition.pr_number || ''" disabled class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-3 text-slate-100 disabled:opacity-100" />
              </div>

              <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="relative pt-0">
                  <label class="absolute left-3 -top-0.5 z-10 -translate-y-1/2 bg-slate-800 px-1 text-sm text-slate-300">PR Date</label>
                  <EnhancedDatePicker
                    :model-value="purchaseRequisition.pr_date || ''"
                    disabled
                    placeholder="dd/mm/yyyy"
                    input-class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-3 text-slate-100 placeholder-transparent"
                  />
                </div>
                <div class="relative pt-0">
                  <label class="absolute left-3 -top-0.5 z-10 -translate-y-1/2 bg-slate-800 px-1 text-sm text-slate-300">Request Date</label>
                  <EnhancedDatePicker
                    :model-value="purchaseRequisition.request_date || ''"
                    disabled
                    placeholder="dd/mm/yyyy"
                    input-class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-3 text-slate-100 placeholder-transparent"
                  />
                </div>
              </div>

              <div>
                <label class="mb-1 block text-sm text-slate-300">Priority</label>
                <input :value="formatPriority(purchaseRequisition.priority)" disabled class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-3 text-slate-100 disabled:opacity-100" />
              </div>
            </div>

            <div class="space-y-4">
              <div>
                <label class="mb-1 block text-sm text-slate-300">Requestor</label>
                <input :value="purchaseRequisition.requester_name || '-'" disabled class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-3 text-slate-100 disabled:opacity-100" />
              </div>
              <div>
                <label class="mb-1 block text-sm text-slate-300">Department</label>
                <input :value="purchaseRequisition.department_name || '-'" disabled class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-3 text-slate-100 disabled:opacity-100" />
              </div>
            </div>
          </div>

          <div class="overflow-hidden rounded-lg border border-slate-700">
            <div class="border-b border-slate-700 bg-slate-900 px-4 py-3">
              <h3 class="font-semibold text-slate-100">Items</h3>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full table-auto">
                <thead>
                  <tr class="text-left text-slate-400">
                    <th class="py-2 pl-4">#</th>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>UoM</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, index) in purchaseRequisition.items" :key="index" class="border-t border-slate-700">
                    <td class="py-3 pl-4">{{ index + 1 }}</td>
                    <td>{{ item.product_name }}</td>
                    <td>{{ item.qty }}</td>
                    <td>{{ item.uom }}</td>
                  </tr>
                  <tr v-if="!purchaseRequisition.items?.length">
                    <td colspan="4" class="py-4 text-center text-sm text-slate-400">Tidak ada item.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="space-y-3 rounded-lg border border-slate-700 p-4">
            <div>
              <label class="block text-sm font-medium text-slate-200">Attachments</label>
            </div>

            <div v-if="purchaseRequisition.attachments?.length" class="space-y-2">
              <div v-for="attachment in purchaseRequisition.attachments" :key="attachment.id" class="flex items-center justify-between rounded bg-slate-900 px-3 py-2 text-sm">
                <a :href="attachment.url" target="_blank" rel="noopener noreferrer" class="min-w-0 truncate text-slate-100 hover:text-indigo-300">
                  {{ attachment.filename }}
                </a>
                <span class="ml-2 text-xs text-slate-400">{{ attachment.size }}</span>
              </div>
            </div>
            <div v-else class="text-sm text-slate-400">Tidak ada attachment.</div>
          </div>

          <div>
            <label class="mb-1 block text-sm text-slate-300">Note</label>
            <textarea :value="purchaseRequisition.note || ''" rows="4" disabled class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-3 text-slate-100 disabled:opacity-100"></textarea>
          </div>

          <div class="flex flex-col-reverse gap-3 border-t border-slate-700 pt-4 sm:flex-row sm:justify-end">
            <Link href="/gmisl/procurement/purchase-requisition" class="rounded bg-slate-700 px-4 py-2 text-center text-white hover:bg-slate-600">Close</Link>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EnhancedDatePicker from '@/Components/EnhancedDatePicker.vue';

defineProps({
  purchaseRequisition: { type: Object, required: true },
  currentUser: { type: Object, default: () => ({}) },
});

function formatPriority(priority) {
  const normalized = String(priority || '').trim().toLowerCase();
  if (normalized === 'urgent') return 'Urgent';
  if (normalized === 'low') return 'Low';
  return 'Medium';
}
</script>