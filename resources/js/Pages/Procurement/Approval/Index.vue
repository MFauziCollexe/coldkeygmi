<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mx-auto max-w-5xl space-y-4">
        <div>
          <h2 class="text-2xl font-bold">Procurement Approval</h2>
          <p class="text-sm text-slate-400">Daftar PR yang sudah memiliki komparasi vendor dan menunggu review Owner.</p>
        </div>

        <div class="rounded-lg border border-slate-700 bg-slate-800 p-4">
          <div v-if="purchaseRequisitions.length" class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-sm text-slate-200">
              <thead>
                <tr class="border-b border-slate-700 text-left text-xs uppercase tracking-wide text-slate-400">
                  <th class="px-3 py-3">PR Number</th>
                  <th class="px-3 py-3">PR Date</th>
                  <th class="px-3 py-3">Requester</th>
                  <th class="px-3 py-3">Department</th>
                  <th class="px-3 py-3">Vendor</th>
                  <th class="px-3 py-3">Selection</th>
                  <th class="px-3 py-3 text-right">Action</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="purchaseRequisition in purchaseRequisitions"
                  :key="purchaseRequisition.id"
                  class="border-b border-slate-800"
                >
                  <td class="px-3 py-3 font-medium text-white">{{ purchaseRequisition.pr_number }}</td>
                  <td class="px-3 py-3">{{ purchaseRequisition.pr_date || '-' }}</td>
                  <td class="px-3 py-3">{{ purchaseRequisition.requester_name || '-' }}</td>
                  <td class="px-3 py-3">{{ purchaseRequisition.department_name || '-' }}</td>
                  <td class="px-3 py-3">{{ purchaseRequisition.supplier_count }}</td>
                  <td class="px-3 py-3">
                    <span
                      class="rounded px-2 py-1 text-xs font-semibold"
                      :class="purchaseRequisition.has_selection ? 'bg-emerald-600/20 text-emerald-300' : 'bg-amber-600/20 text-amber-300'"
                    >
                      {{ purchaseRequisition.has_selection ? `${purchaseRequisition.selected_count} item selected` : 'Belum ada pilihan' }}
                    </span>
                  </td>
                  <td class="px-3 py-3 text-right">
                    <Link
                      :href="`/gmisl/procurement/purchase-requisition/${purchaseRequisition.id}?return_to=${encodeURIComponent('/gmisl/procurement/approval')}`"
                      class="inline-flex rounded bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700"
                    >
                      Review
                    </Link>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="py-8 text-center text-sm text-slate-400">
            Belum ada PR yang masuk ke approval procurement.
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
  purchaseRequisitions: { type: Array, default: () => [] },
  currentUser: { type: Object, default: () => ({}) },
});
</script>
