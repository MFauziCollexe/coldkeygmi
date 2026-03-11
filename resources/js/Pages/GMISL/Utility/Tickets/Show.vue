<template>
  <AppLayout>
    <div class="p-6 max-w-4xl">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold">Ticket Details</h2>
        <Link href="/tickets" class="text-indigo-400 hover:underline text-sm">← Back to List</Link>
      </div>

      <div class="bg-slate-800 rounded p-6 space-y-4">
        <!-- Header -->
        <div class="flex justify-between items-center border-b border-slate-700 pb-4">
          <div>
            <div class="text-sm text-slate-400">Ticket Number</div>
            <div class="text-xl font-bold">{{ ticket.ticket_number }}</div>
          </div>
          <div class="text-right">
            <div class="text-sm text-slate-400 mb-2">Status</div>
            <div class="px-3 py-2 rounded bg-slate-700 text-white inline-block">
              {{ ticket.status.replace(/_/g, ' ').toUpperCase() }}
            </div>
          </div>
        </div>

        <!-- Ticket Info -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <div class="text-sm text-slate-400">Title</div>
            <div class="font-semibold">{{ ticket.title }}</div>
          </div>
          <div>
            <div class="text-sm text-slate-400">Department</div>
            <div>{{ ticket.department?.name || '-' }}</div>
          </div>
        </div>

        <div>
          <div class="text-sm text-slate-400">Description</div>
          <div class="w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-slate-500">{{ ticket.description }}</div>
        </div>

        <!-- Resolution Notes Section -->
        <div v-if="ticket.resolution_notes" class="border-t border-slate-700 pt-4">
          <div class="bg-slate-700/50 p-4 rounded">
            <h3 class="font-bold mb-2"> Notes</h3>
            <p class="text-sm text-slate-300 whitespace-pre-wrap">{{ ticket.resolution_notes }}</p>
          </div>
        </div>

        <!-- Deadlines -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <div class="text-sm text-slate-400">Deadline</div>
            <div :class="getDeadlineClass(ticket.deadline)">
              {{ ticket.deadline ? new Date(ticket.deadline).toLocaleDateString() : '-' }}
            </div>
          </div>
          <div>
            <div class="text-sm text-slate-400">Resolve Deadline</div>
            <div v-if="ticket.resolve_deadline" :class="getDeadlineClass(ticket.resolve_deadline)">
              {{ new Date(ticket.resolve_deadline).toLocaleDateString() }}
            </div>
            <div v-else class="text-slate-400">-</div>
          </div>
        </div>

        <!-- Attachments -->
        <div v-if="ticket.attachments && ticket.attachments.length > 0">
          <div class="text-sm text-slate-400 mb-2">Attachments</div>
          <div class="grid grid-cols-3 gap-3">
            <div 
              v-for="file in ticket.attachments" 
              :key="file.id"
              class="bg-slate-900 p-2 rounded cursor-pointer hover:bg-slate-800"
              @click="viewFile(file)"
            >
              <img v-if="isImage(file.filename)" :src="file.url" class="w-full h-24 object-cover rounded" />
              <div v-else class="w-full h-24 bg-slate-700 rounded flex items-center justify-center">
                <span class="text-xs text-slate-400">{{ getFileExt(file.filename) }}</span>
              </div>
              <p class="text-xs text-slate-400 mt-1 truncate">{{ file.filename }}</p>
              
              <!-- Delete for Manager/IT -->
              <button 
                v-if="isManager"
                @click.stop="deleteAttachment(file.id)"
                :disabled="ticket.status === 'Closed' || ticket.status === 'Resolved'"
                class="text-red-400 text-xs mt-1 hover:text-red-300 disabled:opacity-50 disabled:cursor-not-allowed"
              >Delete</button>
            </div>
          </div>
        </div>

        <!-- Manager Section -->
        <div v-if="isManager" class="border-t border-slate-700 pt-4">
          <div class="bg-slate-700/50 p-4 rounded">
            <h3 class="font-bold mb-3">Manager Actions - Distribute Ticket</h3>
            <div v-if="ticket.status === 'Resolved' || ticket.status === 'Closed'" class="p-3 bg-slate-600/50 rounded mb-3">
              <p class="text-sm text-slate-300">Cannot distribute ticket while it is {{ ticket.status }}.</p>
            </div>
            <div class="space-y-2">
              <p class="text-sm text-slate-300">
                Distribute this ticket to employees in the {{ ticket.department?.name }} department:
              </p>
              <div class="flex gap-2 flex-wrap">
                <button 
                  v-for="emp in departmentEmployees" 
                  :key="emp.id"
                  @click="assignToEmployee(emp)"
                  :disabled="ticket.status === 'Resolved' || ticket.status === 'Closed'"
                  :class="ticket.assigned_to === emp.id 
                    ? 'px-4 py-2 bg-green-600 rounded text-sm text-white font-bold hover:bg-green-500 transition-colors duration-200 ring-2 ring-green-400 disabled:opacity-50' 
                    : 'px-4 py-2 bg-indigo-600 rounded text-sm text-white font-medium hover:bg-indigo-500 transition-colors duration-200 disabled:opacity-50'"
                >
                  {{ emp.name }}
                  <span v-if="ticket.assigned_to === emp.id" class="ml-2">✓</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Assignee Section -->
        <div v-if="isAssignee" class="border-t border-slate-700 pt-4">
          <div class="bg-slate-700/50 p-4 rounded">
            <h3 class="font-bold mb-3">My Actions</h3>
            
            <!-- Pending deadline change notice -->
            <div v-if="ticket.deadline_request && !ticket.deadline_approved" class="mb-3 p-3 bg-yellow-600/30 border border-yellow-600 rounded">
              <p class="text-sm text-yellow-400">
                ⏳ Waiting for creator approval on deadline change request. You cannot change status or resolve until it's approved.
              </p>
            </div>
            
            <div class="flex gap-2 flex-wrap items-center">
              <!-- Change Status Buttons -->
              <button 
                @click="changeStatus('In Progress')"
                :disabled="ticket.status === 'Resolved' || ticket.status === 'Closed' || (ticket.deadline_request && !ticket.deadline_approved)"
                class="px-3 py-2 bg-yellow-600 rounded text-white text-sm hover:bg-yellow-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                In Progress
              </button>
              <button 
                @click="changeStatus('On Hold')"
                :disabled="ticket.status === 'Resolved' || ticket.status === 'Closed' || (ticket.deadline_request && !ticket.deadline_approved)"
                class="px-3 py-2 bg-orange-600 rounded text-white text-sm hover:bg-orange-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                On Hold
              </button>

              <!-- Request Deadline Change -->
              <button 
                v-if="ticket.status !== 'Resolved' && ticket.status !== 'Closed'"
                @click="showDeadlineModal = true"
                :disabled="ticket.deadline_request && !ticket.deadline_approved"
                class="px-3 py-2 bg-blue-600 rounded text-white text-sm hover:bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Request Deadline Change
              </button>

              <!-- Resolve Ticket -->
              <button 
                v-if="ticket.status !== 'Resolved' && ticket.status !== 'Closed'"
                @click="showResolveModal = true"
                :disabled="ticket.deadline_request && !ticket.deadline_approved"
                class="px-3 py-2 bg-green-600 rounded text-white text-sm hover:bg-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Resolve Ticket
              </button>
            </div>
          </div>
        </div>

        <!-- Creator Section -->
        <div v-if="isCreator" class="border-t border-slate-700 pt-4">
          <div class="bg-slate-700/50 p-4 rounded space-y-4">
            <!-- Pending deadline change request -->
            <div v-if="ticket.deadline_request && !ticket.deadline_approved" class="space-y-3 bg-slate-600/50 p-3 rounded">
              <h3 class="font-bold">Pending Deadline Change Request</h3>
              <p class="text-sm text-slate-300">
                Assignee requested to change deadline from 
                <span class="font-bold">{{ ticket.deadline ? new Date(ticket.deadline).toLocaleDateString() : '-' }}</span>
                to 
                <span class="font-bold text-yellow-400">{{ new Date(ticket.deadline_request).toLocaleDateString() }}</span>
              </p>
              <div class="flex gap-2">
                <button @click="approveDeadlineChange(true)" class="px-4 py-2 bg-green-600 rounded text-white text-sm hover:bg-green-500">
                  Approve
                </button>
                <button @click="approveDeadlineChange(false)" class="px-4 py-2 bg-red-600 rounded text-white text-sm hover:bg-red-500">
                  Reject
                </button>
              </div>
            </div>

            <!-- When status is resolved - approve/reject -->
            <div v-if="ticket.status === 'Resolved'" class="space-y-3">
              <h3 class="font-bold mb-3">Review Resolution</h3>
              <p class="text-sm text-slate-300">Assignee has resolved this ticket. Approve the resolution to close it, or reject to request more work.</p>
              <div class="flex gap-2">
                <button @click="closeTicket()" class="px-4 py-2 bg-green-600 rounded text-white hover:bg-green-500">
                  Close Ticket
                </button>
                <button @click="rejectResolution()" class="px-4 py-2 bg-red-600 rounded text-white hover:bg-red-500">
                  Reject (Request More Work)
                </button>
              </div>
            </div>

            <!-- When status is closed - reopen -->
            <div v-if="ticket.status === 'Closed'" class="space-y-3">
              <h3 class="font-bold mb-3">Ticket Closed</h3>
              <p class="text-sm text-slate-300">This ticket is closed and complete. You can reopen it if more work is needed.</p>
              <button @click="reopenTicket()" class="px-4 py-2 bg-blue-600 rounded text-white hover:bg-blue-500">
                Reopen for More Work
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Deadline Change Modal -->
      <div v-if="showDeadlineModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50" @click="showDeadlineModal = false">
        <div class="bg-slate-800 p-6 rounded max-w-md" @click.stop>
          <h3 class="text-lg font-bold mb-4">Request Deadline Change</h3>
          <div class="mb-4">
            <label class="block text-sm text-slate-300 mb-2">New Deadline *</label>
            <EnhancedDatePicker v-model="newDeadline" placeholder="dd/mm/yyyy" />
          </div>
          <div class="mb-4">
            <label class="block text-sm text-slate-300 mb-2">Reason for Change</label>
            <textarea v-model="deadlineReason" class="w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-slate-500" rows="3"></textarea>
          </div>
          <div class="flex justify-end gap-2">
            <button @click="showDeadlineModal = false" class="px-4 py-2 rounded bg-slate-700">Cancel</button>
            <button @click="requestDeadlineChange" class="px-4 py-2 rounded bg-blue-600 text-white">Request</button>
          </div>
        </div>
      </div>

      <!-- Resolve Modal -->
      <div v-if="showResolveModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50" @click="showResolveModal = false">
        <div class="bg-slate-800 p-6 rounded max-w-md" @click.stop>
          <h3 class="text-lg font-bold mb-4">Resolve Ticket</h3>
          <div class="mb-4">
            <label class="block text-sm text-slate-300 mb-2">Resolution Notes *</label>
            <textarea v-model="resolutionNotes" class="w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-slate-500" rows="4" placeholder="Describe how you resolved this ticket..."></textarea>
          </div>
          <div class="mb-4">
            <label class="block text-sm text-slate-300 mb-2">Attach File (optional)</label>
            <input type="file" @change="handleResolutionFile" class="w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-slate-500" />
          </div>
          <div class="flex justify-end gap-2">
            <button @click="showResolveModal = false" class="px-4 py-2 rounded bg-slate-700">Cancel</button>
            <button @click="resolveTicket" class="px-4 py-2 rounded bg-green-600 text-white">Resolve</button>
          </div>
        </div>
      </div>

      <!-- File Preview Modal -->
      <div v-if="showFileModal" class="fixed inset-0 bg-black/80 flex items-center justify-center z-50" @click="showFileModal = false">
        <div @click.stop>
          <img v-if="previewIsImage" :src="previewFile" class="max-h-screen w-auto" />
          <div v-else class="bg-slate-800 p-8 rounded text-center">
            <p class="text-slate-300">File preview not available</p>
            <a :href="previewFile" target="_blank" class="text-indigo-400 hover:underline">Download File</a>
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
import { router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import EnhancedDatePicker from '@/Components/EnhancedDatePicker.vue';

const props = defineProps({
  ticket: Object,
  isManager: Boolean,
  isAssignee: Boolean,
  isCreator: Boolean,
  departmentEmployees: Array,
  canReopen: Boolean,
});

const currentStatus = ref(props.ticket.status);
const showDeadlineModal = ref(false);
const newDeadline = ref('');
const deadlineReason = ref('');
const showResolveModal = ref(false);
const resolutionNotes = ref('');
const resolutionFile = ref(null);
const showFileModal = ref(false);
const previewFile = ref('');
const previewIsImage = ref(false);

function changeStatus(status) {
  router.patch(`/tickets/${props.ticket.id}`, { status: status });
}

function getDeadlineClass(deadline) {
  const today = new Date();
  const deadlineDate = new Date(deadline);
  const daysLeft = Math.ceil((deadlineDate - today) / (1000 * 60 * 60 * 24));
  
  const base = 'font-semibold';
  if (daysLeft < 0) return `${base} text-red-400`; // Overdue
  if (daysLeft <= 2) return `${base} text-yellow-400`; // Within 2 days
  return `${base} text-green-400`; // Safe
}

function isImage(fileName) {
  const ext = fileName.split('.').pop().toLowerCase();
  return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext);
}

function getFileExt(fileName) {
  return fileName.split('.').pop().toUpperCase();
}

function viewFile(file) {
  if (isImage(file.filename)) {
    previewFile.value = file.url;
    previewIsImage.value = true;
  } else {
    previewFile.value = file.url;
    previewIsImage.value = false;
  }
  showFileModal.value = true;
}

function deleteAttachment(fileId) {
  Swal.fire({
    title: 'Delete Attachment?',
    text: 'Are you sure you want to delete this attachment?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Delete',
    cancelButtonText: 'Cancel'
  }).then((result) => {
    if (result.isConfirmed) {
      router.delete(`/tickets/${props.ticket.id}/attachments/${fileId}`);
    }
  });
}

function assignToEmployee(employee) {
  router.patch(`/tickets/${props.ticket.id}`, { assigned_to: employee.id });
}

function requestDeadlineChange() {
  if (!newDeadline.value) {
    Swal.fire({
      icon: 'error',
      title: 'Oops!',
      text: 'Please select a new deadline',
      confirmButtonColor: '#4f46e5'
    });
    return;
  }
  
  router.post(`/tickets/${props.ticket.id}/request-deadline`, {
    deadline_request: newDeadline.value,
  }, {
    onSuccess: () => {
      showDeadlineModal.value = false;
      newDeadline.value = '';
      deadlineReason.value = '';
    },
  });
}

function handleResolutionFile(e) {
  resolutionFile.value = e.target.files[0];
}

function resolveTicket() {
  if (!resolutionNotes.value) {
    Swal.fire({
      icon: 'error',
      title: 'Oops!',
      text: 'Please provide resolution notes',
      confirmButtonColor: '#4f46e5'
    });
    return;
  }
  
  const formData = new FormData();
  formData.append('resolution_notes', resolutionNotes.value);
  if (resolutionFile.value) {
    formData.append('attachment', resolutionFile.value);
  }
  
  router.post(`/tickets/${props.ticket.id}/resolve`, formData, {
    forceFormData: true,
    onSuccess: () => {
      showResolveModal.value = false;
      resolutionNotes.value = '';
      resolutionFile.value = null;
    },
  });
}

function approveResolution() {
  closeTicket();
}

function approveDeadlineChange(approve) {
  router.post(`/tickets/${props.ticket.id}/approve-deadline`, { approve: approve }, {
    onSuccess: () => {
      // Page will reload with updated ticket data
    },
  });
}

function closeTicket() {
  Swal.fire({
    title: 'Close Ticket?',
    text: 'This ticket will be marked as complete.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#16a34a',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Close',
    cancelButtonText: 'Cancel'
  }).then((result) => {
    if (result.isConfirmed) {
      router.patch(`/tickets/${props.ticket.id}`, { status: 'Closed' });
    }
  });
}

function rejectResolution() {
  Swal.fire({
    title: 'Reject Resolution?',
    text: 'This will send the ticket back to the assignee for more work.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Reject',
    cancelButtonText: 'Cancel'
  }).then((result) => {
    if (result.isConfirmed) {
      router.patch(`/tickets/${props.ticket.id}`, { status: 'In Progress' });
    }
  });
}

function reopenTicket() {
  Swal.fire({
    title: 'Reopen Ticket?',
    text: 'This ticket will be reopened for more work.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#2563eb',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Reopen',
    cancelButtonText: 'Cancel'
  }).then((result) => {
    if (result.isConfirmed) {
      router.post(`/tickets/${props.ticket.id}/reopen`);
    }
  });
}
</script>

