<template>
  <div v-if="show" class="modal-overlay fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-2 sm:p-4" @click.self="closeModal">
    <div class="modal-content flex max-h-[96vh] w-full max-w-5xl flex-col overflow-hidden rounded-lg border border-slate-700 bg-slate-900">
      <div class="flex flex-shrink-0 items-center justify-between border-b border-slate-700 bg-slate-800 px-4 py-3">
        <div class="min-w-0">
          <h3 class="truncate font-semibold text-slate-100">Sign: {{ attachment?.filename }}</h3>
          <p class="truncate text-xs text-slate-400">
            Original uploaded by: {{ attachment?.uploader_name || 'Unknown' }}
          </p>
        </div>
        <button @click="closeModal" class="ml-2 flex-shrink-0 text-slate-400 hover:text-white">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="flex-1 overflow-y-auto p-3 sm:p-4">
        <div class="rounded bg-slate-800/50 px-3 py-2 text-sm text-slate-300">
          Draw your signature in the box below. Use the toolbar to adjust pen color and size.
        </div>

        <div class="mt-4 flex justify-center">
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

        <div v-if="errorMessage" class="mt-4 rounded border border-rose-500 bg-rose-900/30 px-3 py-2 text-sm text-rose-300">
          {{ errorMessage }}
        </div>
      </div>

      <div class="flex flex-col-reverse gap-2 border-t border-slate-700 bg-slate-800 px-3 py-3 sm:flex-row sm:items-center sm:justify-end sm:px-4">
        <button
          @click="closeModal"
          class="w-full rounded bg-slate-700 px-4 py-2 text-sm text-white hover:bg-slate-600 sm:w-auto"
          type="button"
        >
          Cancel
        </button>
        <button
          @click="submitSignature"
          :disabled="isSubmitting || !hasSignature"
          class="w-full rounded bg-emerald-600 px-4 py-2 text-sm text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
          type="button"
        >
          {{ isSubmitting ? 'Signing...' : 'Sign & Submit' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import SignaturePad from '@/Components/SignaturePad.vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  attachment: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close', 'signed']);

const signaturePadRef = ref(null);
const isSubmitting = ref(false);
const errorMessage = ref('');
const penColor = ref('#000000');
const penWidth = ref(3);
const viewportWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1280);
const viewportHeight = ref(typeof window !== 'undefined' ? window.innerHeight : 800);

const canvasWidth = computed(() => {
  if (viewportWidth.value < 640) {
    return Math.max(280, viewportWidth.value - 48);
  }

  if (viewportWidth.value < 1024) {
    return Math.min(760, viewportWidth.value - 96);
  }

  return 960;
});

const canvasHeight = computed(() => {
  const reservedSpace = viewportWidth.value < 640 ? 260 : 300;
  return Math.max(260, Math.min(700, viewportHeight.value - reservedSpace));
});

const attachmentUrl = computed(() => {
  return props.attachment?.original_url || props.attachment?.url || '';
});

const hasSignature = computed(() => {
  return signaturePadRef.value?.hasSignature?.() || false;
});

watch(() => props.show, (newVal) => {
  if (newVal) {
    errorMessage.value = '';
    return;
  }

  setTimeout(() => {
    signaturePadRef.value?.clearSignature?.();
  }, 300);
});

function syncViewport() {
  viewportWidth.value = window.innerWidth;
  viewportHeight.value = window.innerHeight;
}

onMounted(() => {
  if (typeof window !== 'undefined') {
    window.addEventListener('resize', syncViewport);
  }
});

onUnmounted(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('resize', syncViewport);
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
    const payload = new FormData();

    payload.append('signature_data', signatureData);
    payload.append('position_x', position.x);
    payload.append('position_y', position.y);
    payload.append('scale', 1.0);
    payload.append('output_format', 'jpg');

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
  console.log('Signature created:', data);
}

function handleError(msg) {
  errorMessage.value = msg;
}
</script>

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

body.modal-open {
  overflow: hidden;
}
</style>
