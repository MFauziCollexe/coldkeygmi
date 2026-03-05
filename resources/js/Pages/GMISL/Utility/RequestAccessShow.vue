<template>
  <AppLayout>
    <div class="p-6 max-w-3xl">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold">Request Access Details</h2>
        <Link href="/request-access" class="text-indigo-400">← Back to List</Link>
      </div>

      <div class="bg-slate-800 rounded p-6 space-y-4">
        <!-- Request Number & Status -->
        <div class="flex justify-between items-center border-b border-slate-700 pb-4">
          <div>
            <div class="text-sm text-slate-400">Request Number</div>
            <div class="text-xl font-bold">{{ request.request_number }}</div>
          </div>
          <div>
            <span :class="getStatusClass(request.status)">
              {{ request.status.toUpperCase() }}
            </span>
          </div>
        </div>

        <!-- Request Type -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <div class="text-sm text-slate-400">Request Type</div>
            <div :class="getTypeClass(request.type) + ' px-2 py-1 rounded inline-block mt-1'">
              {{ request.type === 'existing_user' ? 'Existing User' : 'New User' }}
            </div>
          </div>
          <div>
            <div class="text-sm text-slate-400">Modules</div>
            <div class="flex flex-wrap gap-1 mt-1">
              <span v-for="mod in formatModules(request.module_keys)" :key="mod" class="bg-slate-700 px-2 py-1 rounded text-sm">
                {{ mod }}
              </span>
            </div>
          </div>
        </div>

        <!-- User Info -->
        <div v-if="request.type === 'existing_user'" class="grid grid-cols-2 gap-4">
          <div>
            <div class="text-sm text-slate-400">User Name</div>
            <div>{{ request.user ? request.user.name : '-' }}</div>
          </div>
          <div>
            <div class="text-sm text-slate-400">Email</div>
            <div>{{ request.user?.email || '-' }}</div>
          </div>
        </div>

        <!-- New User Info -->
        <div v-if="request.type === 'new_user'" class="grid grid-cols-2 gap-4">
          <div>
            <div class="text-sm text-slate-400">New User Name</div>
            <div>{{ request.target_user_name || '-' }}</div>
          </div>
          <div>
            <div class="text-sm text-slate-400">New User Email</div>
            <div>{{ request.target_user_email || '-' }}</div>
          </div>
          <div>
            <div class="text-sm text-slate-400">Department</div>
            <div>{{ request.target_department?.name || '-' }}</div>
          </div>
        </div>

        <!-- Reason -->
        <div>
          <div class="text-sm text-slate-400">Reason</div>
          <div class=w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-slate-500>{{ request.reason }}</div>
        </div>

        <!-- Review Info (if reviewed) -->
        <div v-if="request.status !== 'pending'" class="border-t border-slate-700 pt-4">
          <div class="text-sm text-slate-400 mb-2">Manager Review</div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <div class="text-xs text-slate-500">Reviewed By</div>
              <div>{{ request.reviewer ? request.reviewer.name : '-' }}</div>
            </div>
            <div>
              <div class="text-xs text-slate-500">Reviewed At</div>
              <div>{{ request.reviewed_at ? new Date(request.reviewed_at).toLocaleString() : '-' }}</div>
            </div>
            <div v-if="request.review_notes" class="col-span-2">
              <div class="text-xs text-slate-500">Review Notes</div>
              <div class="bg-slate-900 p-2 rounded">{{ request.review_notes }}</div>
            </div>
          </div>
        </div>

        <!-- Processing Info (if processed) -->
        <div v-if="request.status === 'processed'" class="border-t border-slate-700 pt-4">
          <div class="text-sm text-slate-400 mb-2">IT Processing</div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <div class="text-xs text-slate-500">Processed By</div>
              <div>{{ request.processor ? request.processor.name : '-' }}</div>
            </div>
            <div>
              <div class="text-xs text-slate-500">Processed At</div>
              <div>{{ request.processed_at ? new Date(request.processed_at).toLocaleString() : '-' }}</div>
            </div>
            <div v-if="request.processing_notes" class="col-span-2">
              <div class="text-xs text-slate-500">Processing Notes</div>
              <div class="bg-slate-900 p-2 rounded">{{ request.processing_notes }}</div>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="border-t border-slate-700 pt-4">
          <!-- Pending - Manager can Approve/Reject -->
          <div v-if="request.status === 'pending' && canReview" class="flex gap-4">
            <button @click="showApproveModal = true" class="bg-green-600 px-4 py-2 rounded text-white">
              Approve
            </button>
            <button @click="showRejectModal = true" class="bg-red-600 px-4 py-2 rounded text-white">
              Reject
            </button>
          </div>

          <!-- Approved - IT can Process or Reject -->
          <div v-if="request.status === 'approved' && canProcess" class="flex gap-4">
            <button @click="showProcessModal = true" class="bg-indigo-600 px-4 py-2 rounded text-white">
              Process (Assign Permission)
            </button>
            <button @click="showRejectModal = true" class="bg-red-600 px-4 py-2 rounded text-white">
              Reject
            </button>
          </div>

          <div v-if="!canReview && !canProcess && request.status === 'pending'" class="text-slate-400 text-sm">
            Waiting for manager approval...
          </div>
        </div>
      </div>

      <!-- Approve Modal -->
      <div v-if="showApproveModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50" @click="showApproveModal = false">
        <div class="bg-slate-800 p-6 rounded max-w-md" @click.stop>
          <h3 class="text-lg font-bold mb-4">Approve Request</h3>
          <div class="mb-4">
            <label class="block text-sm text-slate-300 mb-2">Notes (optional)</label>
            <textarea v-model="reviewNotes" class="w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-slate-500" rows="3"></textarea>
          </div>
          <div class="flex justify-end gap-2">
            <button @click="showApproveModal = false" class="px-4 py-2 rounded bg-slate-700">Cancel</button>
            <button @click="approve" class="px-4 py-2 rounded bg-green-600 text-white">Approve</button>
          </div>
        </div>
      </div>

      <!-- Reject Modal -->
      <div v-if="showRejectModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50" @click="showRejectModal = false">
        <div class="bg-slate-800 p-6 rounded max-w-md" @click.stop>
          <h3 class="text-lg font-bold mb-4">Reject Request</h3>
          <div class="mb-4">
            <label class="block text-sm text-slate-300 mb-2">Reason for rejection *</label>
            <textarea v-model="reviewNotes" class="w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-slate-500" rows="3"></textarea>
          </div>
          <div class="flex justify-end gap-2">
            <button @click="showRejectModal = false" class="px-4 py-2 rounded bg-slate-700">Cancel</button>
            <button @click="reject" class="px-4 py-2 rounded bg-red-600 text-white">Reject</button>
          </div>
        </div>
      </div>

      <!-- Process Modal -->
      <div v-if="showProcessModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50" @click="showProcessModal = false">
        <div class="bg-slate-800 p-6 rounded max-w-md" @click.stop>
          <h3 class="text-lg font-bold mb-4">Process Request</h3>
          <div class="mb-4 bg-blue-900/30 p-3 rounded text-sm">
            <p v-if="request.type === 'existing_user'">
              This will assign {{ request.module_keys?.length || 0 }} module permission(s) to the existing user.
            </p>
            <p v-else>
              This will create a new user account and assign {{ request.module_keys?.length || 0 }} module permission(s).
            </p>
          </div>
          <div class="mb-4">
            <label class="block text-sm text-slate-300 mb-2">Notes (optional)</label>
            <textarea v-model="processingNotes" class="w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-slate-500" rows="3"></textarea>
          </div>
          <div class="flex justify-end gap-2">
            <button @click="showProcessModal = false" class="px-4 py-2 rounded bg-slate-700">Cancel</button>
            <button @click="process" class="px-4 py-2 rounded bg-indigo-600 text-white">Process</button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Inertia } from '@inertiajs/inertia';

const props = defineProps({ 
  request: Object,
  canReview: Boolean,
  canProcess: Boolean,
  isAdmin: Boolean,
});

const showApproveModal = ref(false);
const showRejectModal = ref(false);
const showProcessModal = ref(false);
const reviewNotes = ref('');
const processingNotes = ref('');

function getStatusClass(status) {
  const colors = {
    'pending': 'bg-yellow-600 text-white px-3 py-1 rounded text-sm',
    'approved': 'bg-blue-600 text-white px-3 py-1 rounded text-sm',
    'rejected': 'bg-red-600 text-white px-3 py-1 rounded text-sm',
    'processed': 'bg-green-600 text-white px-3 py-1 rounded text-sm',
  };
  return colors[status] || 'bg-slate-600 text-white px-3 py-1 rounded text-sm';
}

function getTypeClass(type) {
  const colors = {
    'existing_user': 'bg-blue-600 text-white',
    'new_user': 'bg-purple-600 text-white',
  };
  return colors[type] || 'bg-slate-600 text-white';
}

function formatModules(moduleKeys) {
  if (!moduleKeys || !Array.isArray(moduleKeys)) return ['-'];
  return moduleKeys.map(key => key.replace(/_/g, '.'));
}

function approve() {
  Inertia.post(`/request-access/${props.request.id}/approve`, {
    review_notes: reviewNotes.value,
  }, {
    onSuccess: () => {
      showApproveModal.value = false;
      reviewNotes.value = '';
    },
  });
}

function reject() {
  if (!reviewNotes.value || reviewNotes.value.trim().length < 5) {
    Swal.fire({
      icon: 'error',
      title: 'Oops!',
      text: 'Please provide a reason for rejection',
      confirmButtonColor: '#4f46e5'
    });
    return;
  }
  
  Inertia.post(`/request-access/${props.request.id}/reject`, {
    review_notes: reviewNotes.value,
  }, {
    onSuccess: () => {
      showRejectModal.value = false;
      reviewNotes.value = '';
    },
  });
}

function process() {
  Inertia.post(`/request-access/${props.request.id}/process`, {
    processing_notes: processingNotes.value,
  }, {
    onSuccess: () => {
      showProcessModal.value = false;
      processingNotes.value = '';
    },
  });
}
</script>
