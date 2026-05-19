<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mx-auto max-w-7xl space-y-4">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 class="text-2xl font-bold">Purchase Requisition</h2>
            <p class="text-sm text-slate-400">Daftar PR dalam list view, detail dibuka lewat popup.</p>
          </div>
          <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <div class="rounded bg-slate-800 px-4 py-2 text-sm text-slate-300">
              {{ currentUser.department_name || '-' }}
            </div>
            <Link href="/master-data/master-item" class="rounded bg-slate-700 px-4 py-2 text-sm font-semibold text-white">
              Master Item
            </Link>
            <Link href="/gmisl/procurement/purchase-requisition/create" class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">
              Create PR
            </Link>
          </div>
        </div>

<div v-if="$page.props.flash?.success" class="rounded border border-green-600 bg-green-600/20 px-4 py-3 text-sm text-green-300">
           {{ $page.props.flash.success }}
         </div>

         <div v-if="$page.props.errors?.vendor" class="rounded border border-rose-600 bg-rose-600/20 px-4 py-3 text-sm text-rose-200">
           {{ $page.props.errors.vendor }}
         </div>

        <section class="space-y-4 rounded bg-slate-800 p-4 md:p-6">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-semibold text-slate-100">PR List</h3>
              <p class="text-sm text-slate-400">Nomor PR, tanggal, requester, department, dan item summary.</p>
            </div>
            <div class="text-sm text-slate-400">{{ purchaseRequisitions.length }} data</div>
          </div>

          <div v-if="!purchaseRequisitions.length" class="rounded border border-dashed border-slate-700 bg-slate-900/40 px-4 py-8 text-center text-sm text-slate-400">
            Belum ada purchase requisition yang bisa dilihat.
          </div>

          <div v-else class="overflow-hidden rounded-lg border border-slate-700">
            <div class="hidden overflow-x-auto lg:block">
              <table class="w-full table-auto">
                <thead class="bg-slate-900/80">
                  <tr class="text-left text-slate-300">
                    <th class="px-4 py-3">No</th>
                    <th class="px-4 py-3">No PR</th>
                    <th class="px-4 py-3">PR Date</th>
                    <th class="px-4 py-3">Requestor</th>
                    <th class="px-4 py-3">Department</th>
                    <th class="px-4 py-3">Item Summary</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(requisition, index) in purchaseRequisitions" :key="requisition.id" class="border-t border-slate-700 align-top">
                    <td class="px-4 py-3 text-slate-300">{{ index + 1 }}</td>
                    <td class="px-4 py-3 font-semibold text-white">{{ requisition.pr_number }}</td>
                    <td class="px-4 py-3 text-slate-300">{{ requisition.pr_date || '-' }}</td>
                    <td class="px-4 py-3 text-slate-300">{{ requisition.requester_name || '-' }}</td>
                    <td class="px-4 py-3 text-slate-300">{{ requisition.department_name || '-' }}</td>
                    <td class="px-4 py-3">
                      <div v-if="requisition.items?.length" class="max-w-sm space-y-1">
                        <div v-for="item in requisition.items.slice(0, 2)" :key="item.id" class="flex items-start justify-between gap-3 text-sm">
                          <div class="min-w-0">
                            <div class="truncate font-medium text-slate-100">{{ item.item_code || '-' }} - {{ item.item_name || '-' }}</div>
                            <div class="truncate text-slate-400">{{ item.description_of_goods || '-' }}</div>
                          </div>
                          <div class="whitespace-nowrap text-slate-300">{{ formatQuantity(item.quantity) }} {{ item.unit || '-' }}</div>
                        </div>
                        <div v-if="requisition.items.length > 2" class="text-xs text-slate-500">+{{ requisition.items.length - 2 }} item lainnya</div>
                      </div>
                      <div v-else class="text-sm text-slate-500">Tidak ada item.</div>
                    </td>
                    <td class="px-4 py-3">
                      <span class="rounded px-2 py-1 text-xs font-semibold" :class="statusClass(requisition.status)">
                        {{ formatStatus(requisition.status) }}
                      </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                      <Link
                        :href="actionHref(requisition)"
                        class="font-medium"
                        :class="requisition.can_edit ? 'text-amber-400 hover:text-amber-300' : 'text-blue-400 hover:text-blue-300'"
                      >
                        {{ requisition.can_edit ? 'Edit' : 'View' }}
                      </Link>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="space-y-3 p-3 lg:hidden">
              <div v-for="(requisition, index) in purchaseRequisitions" :key="`mobile-${requisition.id}`" class="rounded-lg border border-slate-700 bg-slate-900/60 p-4">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <div class="text-xs text-slate-400">No {{ index + 1 }}</div>
                    <div class="font-semibold text-white">{{ requisition.pr_number }}</div>
                    <div class="mt-1 text-sm text-slate-400">PR Date: {{ requisition.pr_date || '-' }}</div>
                  </div>
                  <span class="rounded px-2 py-1 text-xs font-semibold" :class="statusClass(requisition.status)">
                    {{ formatStatus(requisition.status) }}
                  </span>
                </div>

                <div class="mt-3 space-y-1 text-sm text-slate-300">
                  <div><span class="text-slate-400">Requestor:</span> {{ requisition.requester_name || '-' }}</div>
                  <div><span class="text-slate-400">Department:</span> {{ requisition.department_name || '-' }}</div>
                </div>

                <div class="mt-4 flex justify-end gap-3">
                  <Link
                    :href="actionHref(requisition)"
                    class="font-medium"
                    :class="requisition.can_edit ? 'text-amber-400' : 'text-blue-400'"
                  >
                    {{ requisition.can_edit ? 'Edit' : 'View' }}
                  </Link>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  currentUser: { type: Object, default: () => ({}) },
  purchaseRequisitions: { type: Array, default: () => [] },
});

function formatStatus(status) {
  const normalized = String(status || '').trim().toLowerCase();
  if (normalized === 'approved') return 'Approved';
  if (normalized === 'waiting') return 'Waiting';
  if (normalized === 'process') return 'Process';
  if (normalized === 'done') return 'Done';
  if (normalized === 'rejected') return 'Rejected';
  if (normalized === 'draft' || normalized === 'pr') return 'Draft';
  return normalized ? normalized.charAt(0).toUpperCase() + normalized.slice(1) : '-';
}

function statusClass(status) {
  const normalized = String(status || '').trim().toLowerCase();
  if (normalized === 'approved') return 'bg-emerald-700/30 text-emerald-300 border border-emerald-500/40';
  if (normalized === 'waiting') return 'bg-amber-700/30 text-amber-300 border border-amber-500/40';
  if (normalized === 'process') return 'bg-indigo-700/30 text-indigo-300 border border-indigo-500/40';
  if (normalized === 'done') return 'bg-sky-700/30 text-sky-300 border border-sky-500/40';
  if (normalized === 'rejected') return 'bg-rose-700/30 text-rose-300 border border-rose-500/40';
  return 'bg-slate-700/40 text-slate-200 border border-slate-600';
}

function formatQuantity(value) {
  const number = Number(value || 0);
  if (!Number.isFinite(number)) return '0';
  return String(Math.round(number));
}

function actionHref(requisition) {
  if (requisition?.can_edit) {
    return `/gmisl/procurement/purchase-requisition/${requisition.id}/edit`;
  }

  return `/gmisl/procurement/purchase-requisition/${requisition.id}`;
}

</script>
