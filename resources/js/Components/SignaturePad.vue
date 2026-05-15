<template>
  <div class="space-y-4">
    <!-- Toolbar -->
    <div v-if="documentLoaded" class="flex flex-wrap gap-2">
      <button
        @click="clearSignature"
        class="rounded bg-slate-700 px-3 py-1.5 text-sm text-white hover:bg-slate-600"
        type="button"
      >
        Clear Signature
      </button>
      <button
        @click="undo"
        :disabled="!canUndo"
        class="rounded bg-slate-700 px-3 py-1.5 text-sm text-white disabled:opacity-50"
        type="button"
      >
        Undo
      </button>
      <div class="flex items-center gap-2">
        <label class="text-sm text-slate-300">Pen Size:</label>
        <input
          v-model.number="strokeWidth"
          type="range"
          min="1"
          max="10"
          step="0.5"
          class="w-24"
        />
      </div>
      <div class="flex items-center gap-2">
        <label class="text-sm text-slate-300">Color:</label>
        <input v-model="strokeColor" type="color" class="h-8 w-16 border-0 bg-transparent" />
      </div>
    </div>

    <!-- Status Info -->
    <div v-if="isProcessing" class="rounded bg-blue-900/30 p-3 text-sm text-blue-300">
      Processing signature...
    </div>

    <!-- Canvas Container -->
    <div
      v-if="documentUrl"
      class="relative mx-auto w-full overflow-hidden rounded-lg border border-slate-700 bg-slate-900"
      :style="{ maxWidth: canvasWidth + 'px' }"
    >
      <canvas
        ref="canvasEl"
        class="block w-full"
        :style="{ height: canvasHeight + 'px', cursor: isDrawing ? 'crosshair' : 'default' }"
        @mousedown="startDrawing"
        @mousemove="draw"
        @mouseup="stopDrawing"
        @mouseleave="stopDrawing"
        @touchstart.prevent="startDrawingTouch"
        @touchmove.prevent="drawTouch"
        @touchend.prevent="stopDrawing"
      />
    </div>

    <!-- Empty State -->
    <div v-if="!documentLoaded" class="rounded border-2 border-dashed border-slate-700 p-8 text-center">
      <svg class="mx-auto mb-3 h-12 w-12 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      <p class="text-slate-400">No document to sign</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';

const emit = defineEmits(['signed', 'error']);

const props = defineProps({
  documentUrl: { type: String, default: null },
  canvasWidth: { type: Number, default: 800 },
  canvasHeight: { type: Number, default: 600 },
  penColor: { type: String, default: '#000000' },
  penWidth: { type: Number, default: 3 },
});

const canvasEl = ref(null);
let canvas = null;
let isDrawing = false;
let signaturePaths = ref([]); // Reaktif array untuk Vue
let currentPath = null;

const documentLoaded = computed(() => !!props.documentUrl);
const canUndo = computed(() => signaturePaths.value.length > 0);
const strokeColor = ref(props.penColor);
const strokeWidth = ref(props.penWidth);
const isProcessing = ref(false);

// Initialize canvas when component mounted or documentUrl changes
onMounted(() => {
  if (props.documentUrl) {
    initCanvas();
  }
});

watch(() => props.documentUrl, (newUrl) => {
  if (newUrl) {
    // Reset state
    signaturePaths.value = [];
    initCanvas();
  }
});

function initCanvas() {
  if (typeof fabric === 'undefined') {
    console.error('Fabric.js not loaded');
    emit('error', 'Canvas library not loaded');
    return;
  }

  // Dispose existing canvas
  if (canvas) {
    canvas.dispose();
  }

  canvas = new fabric.Canvas(canvasEl.value, {
    width: props.canvasWidth,
    height: props.canvasHeight,
    backgroundColor: 'transparent',
    selection: false,
    isDrawingMode: true,
  });

  // Konfigurasi brush
  canvas.freeDrawingBrush = new fabric.PencilBrush(canvas);
  canvas.freeDrawingBrush.color = strokeColor.value;
  canvas.freeDrawingBrush.width = strokeWidth.value;
  canvas.freeDrawingBrush.strokeLineCap = 'round';
  canvas.freeDrawingBrush.strokeLineJoin = 'round';

  // Load background image
  const imgElement = new Image();
  imgElement.crossOrigin = 'anonymous';
  imgElement.src = props.documentUrl;

  imgElement.onload = () => {
    // Scale to fit canvas while maintaining aspect ratio
    const scale = Math.min(
      props.canvasWidth / imgElement.width,
      props.canvasHeight / imgElement.height
    );

    const scaledWidth = imgElement.width * scale;
    const scaledHeight = imgElement.height * scale;

    canvas.setDimensions({
      width: scaledWidth,
      height: scaledHeight,
    });

    fabric.Image.fromURL(props.documentUrl, (fabricImg) => {
      canvas.setBackgroundImage(fabricImg, canvas.renderAll.bind(canvas), {
        scaleX: scale,
        scaleY: scale,
        originX: 'left',
        originY: 'top',
      });
    });
  };

  imgElement.onerror = () => {
    emit('error', 'Failed to load document image');
  };

  // Capture drawn paths untuk undo
  canvas.on('path:created', (e) => {
    signaturePaths.value.push(e.path);
  });

  // Track drawing state
  canvas.on('path:created', () => {
    isDrawing.value = false;
  });
}

// Manual drawing via mouse events (fallback jika isDrawingMode tidak work)
function startDrawing(e) {
  if (!canvas) return;
  isDrawing.value = true;
  const pointer = canvas.getPointer(e);

  const path = new fabric.Path(
    `M ${pointer.x} ${pointer.y}`,
    {
      stroke: strokeColor.value,
      strokeWidth: strokeWidth.value,
      fill: '',
      selectable: false,
      strokeLineCap: 'round',
      strokeLineJoin: 'round',
      id: 'signature-path',
    }
  );

  canvas.add(path);
  currentPath = path;
}

function draw(e) {
  if (!isDrawing.value || !currentPath || !canvas) return;

  const pointer = canvas.getPointer(e);
  const pathData = currentPath.path;

  // Add line segment
  pathData.push(['L', pointer.x, pointer.y]);
  currentPath.set({ path: pathData });
  canvas.renderAll();
}

function stopDrawing() {
  if (currentPath) {
    signaturePaths.value.push(currentPath);
    currentPath = null;
  }
  isDrawing.value = false;
}

// Touch event handlers
function startDrawingTouch(e) {
  if (e.touches.length === 1) {
    const touch = e.touches[0];
    const mouseEvent = new MouseEvent('mousedown', {
      clientX: touch.clientX,
      clientY: touch.clientY,
    });
    canvasEl.value.dispatchEvent(mouseEvent);
  }
}

function drawTouch(e) {
  if (e.touches.length === 1) {
    const touch = e.touches[0];
    const mouseEvent = new MouseEvent('mousemove', {
      clientX: touch.clientX,
      clientY: touch.clientY,
    });
    canvasEl.value.dispatchEvent(mouseEvent);
  }
}

function clearSignature() {
  if (!canvas) return;

  // Remove semua objects kecuali background
  const objects = canvas.getObjects();
  objects.forEach(obj => {
    if (obj !== canvas.backgroundImage) {
      canvas.remove(obj);
    }
  });
  signaturePaths.value = [];
  canvas.renderAll();
}

function undo() {
  if (!canvas || signaturePaths.value.length === 0) return;

  const lastPath = signaturePaths.value.pop();
  canvas.remove(lastPath);
  canvas.renderAll();
}

/**
 * Export signature layer sebagai transparent PNG base64
 */
function exportSignature() {
  if (!canvas || signaturePaths.value.length === 0) {
    return '';
  }

  // Create temporary canvas untuk signature saja
  const tempCanvas = new fabric.StaticCanvas(null, {
    width: canvas.width,
    height: canvas.height,
  });

  // Clone semua signature paths
  signaturePaths.value.forEach(path => {
    const cloned = fabric.util.object.clone(path);
    tempCanvas.add(cloned);
  });

  // Export ke PNG base64
  return tempCanvas.toDataURL({
    format: 'png',
    quality: 1,
    multiplier: 2, // 2x resolution for crisp signature
  });
}

/**
 * Get signature bounding box normalized (0-1)
 */
function getSignatureBounds() {
  if (signaturePaths.value.length === 0) {
    return { x: 0, y: 0, width: 0, height: 0 };
  }

  if (!canvas) {
    return { x: 0, y: 0, width: 0, height: 0 };
  }

  let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;

  signaturePaths.value.forEach(path => {
    const bounds = path.getBoundingRect();
    minX = Math.min(minX, bounds.left);
    minY = Math.min(minY, bounds.top);
    maxX = Math.max(maxX, bounds.left + bounds.width);
    maxY = Math.max(maxY, bounds.top + bounds.height);
  });

  const canvasWidth = canvas.width;
  const canvasHeight = canvas.height;

  return {
    x: minX / canvasWidth,
    y: minY / canvasHeight,
    width: (maxX - minX) / canvasWidth,
    height: (maxY - minY) / canvasHeight,
  };
}

/**
 * Validate signature is not empty
 */
function hasSignature() {
  return signaturePaths.value.length > 0;
}

// Expose methods for parent component
defineExpose({
  exportSignature,
  getSignatureBounds,
  clearSignature,
  hasSignature,
});

onUnmounted(() => {
  if (canvas) {
    canvas.dispose();
    canvas = null;
  }
});
</script>

<style scoped>
/* Ensure canvas looks sharp on high-DPI displays */
canvas {
  image-rendering: -webkit-optimize-contrast;
  image-rendering: crisp-edges;
}
</style>
