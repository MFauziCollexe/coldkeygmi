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
            <button v-if="canDelete" type="button" class="rounded bg-rose-600 px-4 py-2 text-white hover:bg-rose-700" @click="confirmDelete">
              Delete
            </button>
            <button v-if="canReject" type="button" class="rounded bg-rose-600 px-4 py-2 text-white hover:bg-rose-700" @click="confirmReject">
              Reject
            </button>
            <button v-if="canApprove" type="button" class="rounded bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700" @click="confirmApprove">
              Approve
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EnhancedDatePicker from '@/Components/EnhancedDatePicker.vue';
import Swal from 'sweetalert2';

const props = defineProps({
  purchaseRequisition: { type: Object, required: true },
  currentUser: { type: Object, default: () => ({}) },
});

const canApprove = props.purchaseRequisition.can_approve === true;
const canReject = props.purchaseRequisition.can_reject === true;
const isItUser = String(props.currentUser?.department_code || '').toUpperCase() === 'IT';
const canDelete = isItUser;

function formatPriority(priority) {
  const normalized = String(priority || '').trim().toLowerCase();
  if (normalized === 'urgent') return 'Urgent';
  if (normalized === 'low') return 'Low';
  return 'Medium';
}

function formatStatus(status) {
  const normalized = String(status || '').trim().toLowerCase();
  if (normalized === 'approved') return 'Approved';
  if (normalized === 'waiting') return 'Waiting';
  if (normalized === 'process') return 'Process';
  if (normalized === 'done') return 'Done';
  if (normalized === 'rejected') return 'Rejected';
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

async function confirmApprove() {
  const result = await Swal.fire({
    title: 'Approve Purchase Requisition?',
    text: 'Are you sure you want to approve this PR?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#10b981',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Yes, Approve',
    cancelButtonText: 'No, Cancel',
  });

  if (result.isConfirmed) {
    approve();
  }
}

async function confirmReject() {
  const result = await Swal.fire({
    title: 'Reject Purchase Requisition?',
    text: 'Please enter a reason for rejection:',
    icon: 'warning',
    input: 'textarea',
    inputLabel: 'Rejection Reason',
    inputPlaceholder: 'Enter reason for rejection...',
    inputAttributes: {
      'aria-label': 'Rejection reason',
    },
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Yes, Reject',
    cancelButtonText: 'No, Cancel',
    inputValidator: (value) => {
      if (!value) {
        return 'Please enter a rejection reason!';
      }
      return null;
    },
  });

  if (result.isConfirmed) {
    reject(result.value);
  }
}

function approve() {
  router.post(`/gmisl/procurement/purchase-requisition/${props.purchaseRequisition.id}/approve`, {}, {
    preserveScroll: true,
  });
}

function reject(note) {
  router.post(`/gmisl/procurement/purchase-requisition/${props.purchaseRequisition.id}/reject`, { reject_note: note }, {
    preserveScroll: true,
  });
}

async function confirmDelete() {
  const result = await Swal.fire({
    title: 'Delete Purchase Requisition?',
    text: 'Are you sure you want to delete this PR? This action cannot be undone.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Yes, Delete',
    cancelButtonText: 'No, Cancel',
  });

  if (result.isConfirmed) {
    destroy();
  }
}

function destroy() {
  router.delete(`/gmisl/procurement/purchase-requisition/${props.purchaseRequisition.id}`, {}, {
    preserveScroll: true,
  });
}
</script>