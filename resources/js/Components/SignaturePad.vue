<template>
  <div class="space-y-4">
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

    <div v-if="isProcessing" class="rounded bg-blue-900/30 p-3 text-sm text-blue-300">
      Processing signature...
    </div>

    <div
      v-if="documentUrl"
      ref="canvasContainer"
      class="relative mx-auto w-full overflow-hidden rounded-lg border border-slate-700 bg-slate-900"
      :style="{ maxWidth: `${displayWidth}px` }"
    >
      <canvas
        ref="canvasEl"
        class="block w-full"
        :style="{ height: `${displayHeight}px`, cursor: isDrawing ? 'crosshair' : 'default' }"
        @mousedown="startDrawing"
        @mousemove="draw"
        @mouseup="stopDrawing"
        @mouseleave="stopDrawing"
        @touchstart.prevent="startDrawingTouch"
        @touchmove.prevent="drawTouch"
        @touchend.prevent="stopDrawing"
      />
    </div>

    <div v-if="!documentLoaded" class="rounded border-2 border-dashed border-slate-700 p-8 text-center">
      <svg class="mx-auto mb-3 h-12 w-12 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      <p class="text-slate-400">No document to sign</p>
    </div>
  </div>
</template>

<script setup>
import { Canvas, FabricImage, Path, PencilBrush, StaticCanvas } from 'fabric';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const emit = defineEmits(['signed', 'error']);

const props = defineProps({
  documentUrl: { type: String, default: null },
  canvasWidth: { type: Number, default: 800 },
  canvasHeight: { type: Number, default: 600 },
  penColor: { type: String, default: '#000000' },
  penWidth: { type: Number, default: 3 },
});

const canvasEl = ref(null);
const canvasContainer = ref(null);
const isDrawing = ref(false);
const signaturePaths = ref([]);
const strokeColor = ref(props.penColor);
const strokeWidth = ref(props.penWidth);
const isProcessing = ref(false);
const sourceDimensions = ref({
  width: props.canvasWidth,
  height: props.canvasHeight,
});
const displayWidth = ref(props.canvasWidth);
const displayHeight = ref(props.canvasHeight);

let canvas = null;
let currentPath = null;
let backgroundImage = null;
let resizeObserver = null;

const documentLoaded = computed(() => !!props.documentUrl);
const canUndo = computed(() => signaturePaths.value.length > 0);

onMounted(async () => {
  await nextTick();

  if (typeof ResizeObserver !== 'undefined') {
    resizeObserver = new ResizeObserver(() => {
      updateCanvasDimensions();
    });

    if (canvasContainer.value) {
      resizeObserver.observe(canvasContainer.value);
    }
  }

  if (props.documentUrl) {
    void initCanvas();
  }
});

watch(() => props.documentUrl, (newUrl) => {
  if (!newUrl) {
    return;
  }

  signaturePaths.value = [];
  void initCanvas();
});

watch(
  () => [props.canvasWidth, props.canvasHeight],
  async () => {
    await nextTick();
    updateCanvasDimensions();
  }
);

watch(strokeColor, (value) => {
  if (canvas?.freeDrawingBrush) {
    canvas.freeDrawingBrush.color = value;
  }
});

watch(strokeWidth, (value) => {
  if (canvas?.freeDrawingBrush) {
    canvas.freeDrawingBrush.width = value;
  }
});

async function initCanvas() {
  if (canvas) {
    canvas.dispose();
    canvas = null;
  }

  if (!canvasEl.value) {
    emit('error', 'Canvas element not ready');
    return;
  }

  canvas = new Canvas(canvasEl.value, {
    width: props.canvasWidth,
    height: props.canvasHeight,
    backgroundColor: 'transparent',
    selection: false,
    isDrawingMode: true,
  });

  canvas.freeDrawingBrush = new PencilBrush(canvas);
  canvas.freeDrawingBrush.color = strokeColor.value;
  canvas.freeDrawingBrush.width = strokeWidth.value;
  canvas.freeDrawingBrush.strokeLineCap = 'round';
  canvas.freeDrawingBrush.strokeLineJoin = 'round';

  try {
    backgroundImage = await FabricImage.fromURL(props.documentUrl, {
      crossOrigin: 'anonymous',
    });

    sourceDimensions.value = {
      width: Number(backgroundImage.width || props.canvasWidth),
      height: Number(backgroundImage.height || props.canvasHeight),
    };

    backgroundImage.set({
      selectable: false,
      evented: false,
      excludeFromExport: true,
      left: 0,
      top: 0,
      originX: 'left',
      originY: 'top',
    });

    canvas.backgroundImage = backgroundImage;
    updateCanvasDimensions();
  } catch (error) {
    console.error('Failed to initialize signature canvas:', error);
    emit('error', 'Failed to load document image');
    return;
  }

  canvas.on('path:created', (event) => {
    if (event.path) {
      signaturePaths.value.push(event.path);
    }
    currentPath = null;
    isDrawing.value = false;
  });
}

function updateCanvasDimensions() {
  if (!canvas || !backgroundImage) {
    return;
  }

  const sourceWidth = Number(sourceDimensions.value.width || props.canvasWidth);
  const sourceHeight = Number(sourceDimensions.value.height || props.canvasHeight);
  const availableWidth = Math.max(
    240,
    Math.min(props.canvasWidth, canvasContainer.value?.clientWidth || props.canvasWidth)
  );
  const scale = Math.min(
    availableWidth / sourceWidth,
    props.canvasHeight / sourceHeight
  );

  displayWidth.value = Math.max(1, Math.round(sourceWidth * scale));
  displayHeight.value = Math.max(1, Math.round(sourceHeight * scale));

  canvas.setDimensions({
    width: displayWidth.value,
    height: displayHeight.value,
  });

  backgroundImage.set({
    scaleX: scale,
    scaleY: scale,
  });

  canvas.renderAll();
}

function startDrawing(event) {
  if (!canvas) return;

  isDrawing.value = true;
  const pointer = canvas.getPointer(event);

  currentPath = new Path(`M ${pointer.x} ${pointer.y}`, {
    stroke: strokeColor.value,
    strokeWidth: strokeWidth.value,
    fill: '',
    selectable: false,
    strokeLineCap: 'round',
    strokeLineJoin: 'round',
    id: 'signature-path',
  });

  canvas.add(currentPath);
}

function draw(event) {
  if (!canvas || !currentPath || !isDrawing.value) return;

  const pointer = canvas.getPointer(event);
  const pathData = currentPath.path;

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

function startDrawingTouch(event) {
  if (event.touches.length !== 1) return;

  const touch = event.touches[0];
  const mouseEvent = new MouseEvent('mousedown', {
    clientX: touch.clientX,
    clientY: touch.clientY,
  });

  canvasEl.value.dispatchEvent(mouseEvent);
}

function drawTouch(event) {
  if (event.touches.length !== 1) return;

  const touch = event.touches[0];
  const mouseEvent = new MouseEvent('mousemove', {
    clientX: touch.clientX,
    clientY: touch.clientY,
  });

  canvasEl.value.dispatchEvent(mouseEvent);
}

function clearSignature() {
  if (!canvas) return;

  canvas.getObjects().forEach((object) => {
    canvas.remove(object);
  });

  signaturePaths.value = [];
  currentPath = null;
  canvas.renderAll();
}

function undo() {
  if (!canvas || signaturePaths.value.length === 0) return;

  const lastPath = signaturePaths.value.pop();
  canvas.remove(lastPath);
  canvas.renderAll();
}

function exportSignature() {
  if (!canvas || signaturePaths.value.length === 0) {
    return '';
  }

  const tempCanvasEl = document.createElement('canvas');
  const tempCanvas = new StaticCanvas(tempCanvasEl, {
    width: canvas.width,
    height: canvas.height,
  });

  signaturePaths.value.forEach((pathObject) => {
    const clonedPath = new Path(pathObject.path, {
      stroke: pathObject.stroke,
      strokeWidth: pathObject.strokeWidth,
      fill: '',
      selectable: false,
      evented: false,
      strokeLineCap: pathObject.strokeLineCap,
      strokeLineJoin: pathObject.strokeLineJoin,
      left: pathObject.left,
      top: pathObject.top,
      scaleX: pathObject.scaleX,
      scaleY: pathObject.scaleY,
      angle: pathObject.angle,
    });

    tempCanvas.add(clonedPath);
  });

  const dataUrl = tempCanvas.toDataURL({
    format: 'png',
    quality: 1,
    multiplier: 2,
  });

  tempCanvas.dispose();
  return dataUrl;
}

function getSignatureBounds() {
  if (signaturePaths.value.length === 0 || !canvas) {
    return { x: 0, y: 0, width: 0, height: 0 };
  }

  let minX = Infinity;
  let minY = Infinity;
  let maxX = -Infinity;
  let maxY = -Infinity;

  signaturePaths.value.forEach((path) => {
    const bounds = path.getBoundingRect();
    minX = Math.min(minX, bounds.left);
    minY = Math.min(minY, bounds.top);
    maxX = Math.max(maxX, bounds.left + bounds.width);
    maxY = Math.max(maxY, bounds.top + bounds.height);
  });

  return {
    x: minX / canvas.width,
    y: minY / canvas.height,
    width: (maxX - minX) / canvas.width,
    height: (maxY - minY) / canvas.height,
  };
}

function hasSignature() {
  return signaturePaths.value.length > 0;
}

defineExpose({
  exportSignature,
  getSignatureBounds,
  clearSignature,
  hasSignature,
});

onUnmounted(() => {
  if (resizeObserver) {
    resizeObserver.disconnect();
    resizeObserver = null;
  }

  if (canvas) {
    canvas.dispose();
    canvas = null;
  }
});
</script>

<style scoped>
canvas {
  image-rendering: -webkit-optimize-contrast;
  image-rendering: crisp-edges;
}
</style>
