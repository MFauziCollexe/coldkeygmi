<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <div class="mx-auto max-w-6xl space-y-4">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 class="text-2xl font-bold">Purchase Order</h2>
            <p class="text-sm text-slate-400">Daftar PR approved/process/done untuk tindak lanjut tim FAT.</p>
          </div>
          <div class="rounded bg-slate-800 px-4 py-2 text-sm text-slate-300">
            {{ currentUser.department_name || '-' }}
          </div>
        </div>

        <div v-if="$page.props.flash?.success" class="rounded border border-green-600 bg-green-600/20 px-4 py-3 text-sm text-green-300">
          {{ $page.props.flash.success }}
        </div>

        <section class="space-y-4 rounded bg-slate-800 p-4 md:p-6">
          <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
              <h3 class="text-lg font-semibold text-slate-100">Queue</h3>
              <p class="text-sm text-slate-400">Pilih data untuk proses, update, atau lihat detail purchase order.</p>
            </div>
            <div class="w-full md:w-72">
              <input
                v-model="search"
                type="text"
                placeholder="Search PR, department, requestor"
                class="w-full rounded border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-100"
              />
            </div>
          </div>

          <div v-if="!filteredPurchaseOrders.length" class="rounded border border-dashed border-slate-700 bg-slate-900/40 px-4 py-8 text-center text-sm text-slate-400">
            Tidak ada data purchase order yang sesuai.
          </div>

          <div v-else class="rounded bg-slate-800">
            <div class="hidden overflow-x-auto lg:block">
              <table class="w-full table-auto">
                <thead>
                  <tr class="text-left text-slate-400">
                    <th class="py-2">No</th>
                    <th>No PO</th>
                    <th>PR</th>
                    <th>Req</th>
                    <th>Departement</th>
                    <th>Status</th>
                    <th>Image</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(order, index) in filteredPurchaseOrders" :key="order.id" class="border-t border-slate-700">
                    <td class="py-3">{{ index + 1 }}</td>
                    <td>{{ order.po_number || '-' }}</td>
                    <td>{{ order.pr_number }}</td>
                    <td>{{ order.requester_name || '-' }}</td>
                    <td>{{ order.department_name || '-' }}</td>
                    <td>
                      <span class="rounded px-2 py-1 text-xs font-semibold" :class="statusClass(order.status)">
                        {{ formatStatus(order.status) }}
                      </span>
                    </td>
                    <td>
                      <a
                        v-if="previewableAttachment(order)"
                        :href="previewableAttachment(order).url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="block"
                      >
                        <img :src="previewableAttachment(order).url" class="h-12 w-12 rounded object-cover" />
                      </a>
                      <div v-else class="flex h-12 w-12 items-center justify-center rounded bg-slate-700 text-xs">-</div>
                    </td>
<td class="text-right whitespace-nowrap">
                       <Link
                         :href="order.can_process || order.can_update_po || order.can_done
                           ? `/gmisl/procurement/purchase-order/${order.id}/form`
                           : `/gmisl/procurement/purchase-order/${order.id}`"
                         :class="order.can_process || order.can_update_po || order.can_done ? 'text-indigo-400' : 'text-blue-400'"
                       >
                         View
                       </Link>
                     </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="space-y-3 lg:hidden">
              <div
                v-for="(order, index) in filteredPurchaseOrders"
                :key="`mobile-${order.id}`"
                class="rounded-lg border border-slate-700 bg-slate-900/60 p-4"
              >
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <div class="text-xs text-slate-400">No {{ index + 1 }}</div>
                    <div class="truncate font-semibold">{{ order.pr_number }}</div>
                  </div>
                  <span class="rounded px-2 py-1 text-xs font-semibold" :class="statusClass(order.status)">
                    {{ formatStatus(order.status) }}
                  </span>
                </div>

                <div class="mt-3 grid grid-cols-[48px_1fr] gap-3">
                  <div>
                    <a
                      v-if="previewableAttachment(order)"
                      :href="previewableAttachment(order).url"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="block"
                    >
                      <img :src="previewableAttachment(order).url" class="h-12 w-12 rounded object-cover" />
                    </a>
                    <div v-else class="flex h-12 w-12 items-center justify-center rounded bg-slate-700 text-xs">-</div>
                  </div>

                  <div class="space-y-1 text-sm">
                    <div><span class="text-slate-400">PO:</span> {{ order.po_number || '-' }}</div>
                    <div><span class="text-slate-400">PR:</span> {{ order.pr_number || '-' }}</div>
                    <div><span class="text-slate-400">Req:</span> {{ order.requester_name || '-' }}</div>
                    <div><span class="text-slate-400">Departement:</span> {{ order.department_name || '-' }}</div>
                  </div>
                </div>

<div class="mt-3 flex justify-end gap-3">
                   <Link
                     :href="order.can_process || order.can_update_po || order.can_done
                       ? `/gmisl/procurement/purchase-order/${order.id}/form`
                       : `/gmisl/procurement/purchase-order/${order.id}`"
                     :class="order.can_process || order.can_update_po || order.can_done ? 'text-indigo-400' : 'text-blue-400'"
                   >
                     View
                   </Link>
                 </div>
              </div>
            </div>

            <div class="mt-4 text-sm text-slate-400">
              Showing 1 to {{ filteredPurchaseOrders.length }} of {{ filteredPurchaseOrders.length }} purchase orders
            </div>
          </div>
        </section>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  purchaseOrders: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
  currentUser: { type: Object, default: () => ({}) },
});

const search = ref(String(props.filters?.search || ''));

const filteredPurchaseOrders = computed(() => {
  const keyword = search.value.trim().toLowerCase();
  if (!keyword) return props.purchaseOrders;

  return props.purchaseOrders.filter((order) => {
    const haystack = [order.pr_number, order.department_name, order.requester_name, order.status]
      .filter(Boolean)
      .join(' ')
      .toLowerCase();

    return haystack.includes(keyword);
  });
});

function formatStatus(status) {
  const normalized = String(status || '').trim().toLowerCase();
  if (normalized === 'approved') return 'Approved';
  if (normalized === 'process') return 'Process';
  if (normalized === 'done') return 'Done';
  return normalized ? normalized.charAt(0).toUpperCase() + normalized.slice(1) : '-';
}

function statusClass(status) {
  const normalized = String(status || '').trim().toLowerCase();
  if (normalized === 'approved') return 'bg-emerald-700/30 text-emerald-300 border border-emerald-500/40';
  if (normalized === 'process') return 'bg-indigo-700/30 text-indigo-300 border border-indigo-500/40';
  if (normalized === 'done') return 'bg-sky-700/30 text-sky-300 border border-sky-500/40';
  return 'bg-slate-700/40 text-slate-200 border border-slate-600';
}

function previewableAttachment(order) {
  return (order.attachments || []).find((attachment) => isImageFile(attachment.filename));
}

function isImageFile(filename) {
  const extension = String(filename || '').split('.').pop()?.toLowerCase() || '';
  return ['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(extension);
}
</script>
