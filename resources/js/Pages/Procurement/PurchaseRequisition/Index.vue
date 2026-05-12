<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mx-auto max-w-6xl space-y-4">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 class="text-2xl font-bold">Purchase Requisition</h2>
            <p class="text-sm text-slate-400">Daftar purchase requisition untuk department pembuat dan approver Owner.</p>
          </div>
          <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <div class="rounded bg-slate-800 px-4 py-2 text-sm text-slate-300">
              {{ currentUser.department_name || '-' }}
            </div>
            <Link href="/gmisl/procurement/purchase-requisition/create" class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">
              Create PR
            </Link>
          </div>
        </div>

        <div v-if="$page.props.flash?.success" class="rounded border border-green-600 bg-green-600/20 px-4 py-3 text-sm text-green-300">
          {{ $page.props.flash.success }}
        </div>

        <section class="space-y-4 rounded bg-slate-800 p-4 md:p-6">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-semibold text-slate-100">PR List</h3>
              <p class="text-sm text-slate-400">Monitor status PR, approve, dan edit data yang masih waiting.</p>
            </div>
            <div class="text-sm text-slate-400">{{ purchaseRequisitions.length }} data</div>
          </div>

          <div v-if="!purchaseRequisitions.length" class="rounded border border-dashed border-slate-700 bg-slate-900/40 px-4 py-8 text-center text-sm text-slate-400">
            Belum ada purchase requisition yang bisa dilihat.
          </div>

          <div v-else class="rounded bg-slate-800">
            <div class="hidden overflow-x-auto lg:block">
              <table class="w-full table-auto">
                <thead>
                  <tr class="text-left text-slate-400">
                    <th class="py-2">No</th>
                    <th>PR</th>
                    <th>Req</th>
                    <th>Departement</th>
                    <th>Req Date</th>
                    <th>Create Date</th>
                    <th>Status</th>
                    <th>Image</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(requisition, index) in purchaseRequisitions" :key="requisition.id" class="border-t border-slate-700">
                    <td class="py-3">{{ index + 1 }}</td>
                    <td>{{ requisition.pr_number }}</td>
                    <td>{{ requisition.requester_name || '-' }}</td>
                    <td>{{ requisition.department_name || '-' }}</td>
                    <td>{{ requisition.request_date || '-' }}</td>
                    <td>{{ requisition.created_at || '-' }}</td>
                    <td>
                      <span class="rounded px-2 py-1 text-xs font-semibold" :class="statusClass(requisition.status)">
                        {{ formatStatus(requisition.status) }}
                      </span>
                    </td>
                    <td>
                      <a
                        v-if="previewableAttachment(requisition)"
                        :href="previewableAttachment(requisition).url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="block"
                      >
                        <img :src="previewableAttachment(requisition).url" class="h-12 w-12 rounded object-cover" />
                      </a>
                      <div v-else class="flex h-12 w-12 items-center justify-center rounded bg-slate-700 text-xs">-</div>
                    </td>
                    <td class="text-right whitespace-nowrap">
                      <Link
                        :href="`/gmisl/procurement/purchase-requisition/${requisition.id}`"
                        class="mr-1 text-blue-400"
                      >
                        View
                      </Link>
                      <Link
                        v-if="requisition.can_edit"
                        :href="`/gmisl/procurement/purchase-requisition/${requisition.id}/edit`"
                        class="mr-3 text-amber-400"
                      >
                        Edit
                      </Link>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="space-y-3 lg:hidden">
              <div
                v-for="(requisition, index) in purchaseRequisitions"
                :key="`mobile-${requisition.id}`"
                class="rounded-lg border border-slate-700 bg-slate-900/60 p-4"
              >
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <div class="text-xs text-slate-400">No {{ index + 1 }}</div>
                    <div class="truncate font-semibold">{{ requisition.pr_number }}</div>
                  </div>
                  <span class="rounded px-2 py-1 text-xs font-semibold" :class="statusClass(requisition.status)">
                    {{ formatStatus(requisition.status) }}
                  </span>
                </div>

                <div class="mt-3 grid grid-cols-[48px_1fr] gap-3">
                  <div>
                    <a
                      v-if="previewableAttachment(requisition)"
                      :href="previewableAttachment(requisition).url"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="block"
                    >
                      <img :src="previewableAttachment(requisition).url" class="h-12 w-12 rounded object-cover" />
                    </a>
                    <div v-else class="flex h-12 w-12 items-center justify-center rounded bg-slate-700 text-xs">-</div>
                  </div>

                  <div class="space-y-1 text-sm">
                    <div><span class="text-slate-400">PR:</span> {{ requisition.pr_number || '-' }}</div>
                    <div><span class="text-slate-400">Req:</span> {{ requisition.requester_name || '-' }}</div>
                    <div><span class="text-slate-400">Departement:</span> {{ requisition.department_name || '-' }}</div>
                    <div><span class="text-slate-400">Req Date:</span> {{ requisition.request_date || '-' }}</div>
                    <div><span class="text-slate-400">Create Date:</span> {{ requisition.created_at || '-' }}</div>
                  </div>
                </div>

<div class="mt-3 flex justify-end gap-3">
                   <Link
                     :href="`/gmisl/procurement/purchase-requisition/${requisition.id}`"
                     class="text-blue-400"
                   >
                     View
                   </Link>
                   <Link
                     v-if="requisition.can_edit"
                     :href="`/gmisl/procurement/purchase-requisition/${requisition.id}/edit`"
                     class="text-amber-400"
                   >
                     Edit
                   </Link>
                 </div>
              </div>
            </div>

            <div class="mt-4 text-sm text-slate-400">
              Showing 1 to {{ purchaseRequisitions.length }} of {{ purchaseRequisitions.length }} purchase requisitions
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
  if (normalized === 'draft' || normalized === 'pr') return 'Draft';
  return normalized ? normalized.charAt(0).toUpperCase() + normalized.slice(1) : '-';
}

function statusClass(status) {
  const normalized = String(status || '').trim().toLowerCase();
  if (normalized === 'approved') return 'bg-emerald-700/30 text-emerald-300 border border-emerald-500/40';
  if (normalized === 'waiting') return 'bg-amber-700/30 text-amber-300 border border-amber-500/40';
  if (normalized === 'process') return 'bg-indigo-700/30 text-indigo-300 border border-indigo-500/40';
  if (normalized === 'done') return 'bg-sky-700/30 text-sky-300 border border-sky-500/40';
  return 'bg-slate-700/40 text-slate-200 border border-slate-600';
}

function formatPriority(priority) {
  const normalized = String(priority || '').trim().toLowerCase();
  if (normalized === 'urgent') return 'Urgent';
  if (normalized === 'low') return 'Low';
  return 'Medium';
}

function priorityClass(priority) {
  const normalized = String(priority || '').trim().toLowerCase();
  if (normalized === 'urgent') return 'bg-rose-700/30 text-rose-300 border border-rose-500/40';
  if (normalized === 'low') return 'bg-sky-700/30 text-sky-300 border border-sky-500/40';
  return 'bg-indigo-700/30 text-indigo-300 border border-indigo-500/40';
}

function previewableAttachment(requisition) {
  return (requisition.attachments || []).find((attachment) => isImageFile(attachment.filename));
}

function isImageFile(filename) {
  const extension = String(filename || '').split('.').pop()?.toLowerCase() || '';
  return ['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(extension);
}
</script>
