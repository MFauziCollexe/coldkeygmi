<template>
  <div v-if="show" class="modal-overlay fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4" @click.self="closeModal">
    <div class="modal-content bg-slate-900 border border-slate-700 rounded-lg w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
      
      <!-- Header -->
      <div class="flex items-center justify-between border-b border-slate-700 bg-slate-800 px-4 py-3 flex-shrink-0">
        <div class="min-w-0">
          <h3 class="font-semibold text-slate-100 truncate">Sign: {{ attachment?.filename }}</h3>
          <p class="text-xs text-slate-400 truncate">
            Original uploaded by: {{ attachment?.uploader_name || 'Unknown' }}
          </p>
        </div>
        <button @click="closeModal" class="text-slate-400 hover:text-white flex-shrink-0 ml-2">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Body - Canvas Area -->
      <div class="flex-1 overflow-y-auto p-4 space-y-4">
        <!-- Instructions -->
        <div class="rounded bg-slate-800/50 px-3 py-2 text-sm text-slate-300">
          Draw your signature in the box below. Use the toolbar to adjust pen color and size.
        </div>

        <!-- Signature Canvas -->
        <div class="flex justify-center">
          <SignaturePad
            ref="signaturePadRef"
            :document-url="attachmentUrl"
            :canvas-width="canvasWidth"
            :canvas-height="canvasHeight"
            :pen-color="penColor"
            :pen-width="penWidth"
            @signed="handleSignatureComplete"
            @error="handleError"
          />
        </div>

        <!-- Error message -->
        <div v-if="errorMessage" class="rounded bg-rose-900/30 border border-rose-500 px-3 py-2 text-sm text-rose-300">
          {{ errorMessage }}
        </div>
      </div>

      <!-- Footer Actions -->
      <div class="flex items-center justify-end gap-3 border-t border-slate-700 bg-slate-800 px-4 py-3 flex-shrink-0">
        <button
          @click="closeModal"
          class="rounded bg-slate-700 px-4 py-2 text-sm text-white hover:bg-slate-600"
          type="button"
        >
          Cancel
        </button>
        <button
          @click="submitSignature"
          :disabled="isSubmitting || !hasSignature"
          class="rounded bg-emerald-600 px-4 py-2 text-sm text-white hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed"
          type="button"
        >
          {{ isSubmitting ? 'Signing...' : 'Sign & Submit' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import SignaturePad from '@/Components/SignaturePad.vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  attachment: { 
    type: Object, 
    default: null,
    // Expected: { id, filename, url, uploader_name, signature_status }
  },
});

const emit = defineEmits(['close', 'signed']);

const signaturePadRef = ref(null);
const isSubmitting = ref(false);
const errorMessage = ref('');

const penColor = ref('#000000');
const penWidth = ref(3);
const canvasWidth = 800;
const canvasHeight = 600;

const attachmentUrl = computed(() => {
  // Sign on top of the original upload to avoid stacking signatures.
  return props.attachment?.original_url || props.attachment?.url || '';
});

const hasSignature = computed(() => {
  return signaturePadRef.value?.hasSignature?.() || false;
});

// Watch for modal open/close to clear state
watch(() => props.show, (newVal) => {
  if (newVal) {
    errorMessage.value = '';
  } else {
    // Clear signature when modal closes (optional - kalo mau simpan state, comment out)
    setTimeout(() => {
      signaturePadRef.value?.clearSignature?.();
    }, 300);
  }
});

function closeModal() {
  emit('close');
}

async function submitSignature() {
  if (!signaturePadRef.value) {
    errorMessage.value = 'Signature pad not ready';
    return;
  }

  if (!signaturePadRef.value.hasSignature?.()) {
    errorMessage.value = 'Please provide a signature before submitting.';
    return;
  }

  isSubmitting.value = true;
  errorMessage.value = '';

  try {
    const signatureData = signaturePadRef.value.exportSignature();
    const position = signaturePadRef.value.getSignatureBounds();

    // Prepare payload
    const payload = new FormData();
    payload.append('signature_data', signatureData);
    payload.append('position_x', position.x);
    payload.append('position_y', position.y);
    payload.append('scale', 1.0);
    payload.append('output_format', 'jpg');

    // POST to backend route
    const response = await axios.post(
      `/gmisl/procurement/purchase-requisition/${props.attachment.purchase_requisition_id}/attachments/${props.attachment.id}/sign`,
      payload
    );

    emit('signed', response.data);
    closeModal();

  } catch (error) {
    const message = error.response?.data?.message || error.message || 'Failed to sign attachment';
    errorMessage.value = message;
    console.error('Signature submit error:', error);
  } finally {
    isSubmitting.value = false;
  }
}

function handleSignatureComplete(data) {
  // Optional: handle real-time signature data
  console.log('Signature created:', data);
}

function handleError(msg) {
  errorMessage.value = msg;
}
</script>

<!-- Prevent body scroll when modal open -->
<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.7);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Hide scrollbar during modal */
body.modal-open {
  overflow: hidden;
}
</style>
