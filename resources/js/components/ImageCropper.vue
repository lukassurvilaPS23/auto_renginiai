<template>
  <div class="cropper-overlay" @click.self="onCancel" @touchmove.prevent>
    <div class="cropper-modal">
      <div class="cropper-head">
        <h3 class="cropper-title">Parinkite nuotraukos poziciją</h3>
        <button class="cropper-x" type="button" aria-label="Atšaukti" @click="onCancel">×</button>
      </div>

      <div class="cropper-stage">
        <div
          ref="viewportEl"
          class="cropper-viewport"
          :style="{ aspectRatio: aspectRatio }"
          @mousedown="startDrag"
          @touchstart="startDrag"
          @wheel.prevent="onWheel"
        >
          <img
            v-if="imageSrc"
            ref="imgEl"
            :src="imageSrc"
            class="cropper-img"
            :style="imgStyle"
            draggable="false"
            @load="onImgLoaded"
            @dragstart.prevent
          />
          <div class="cropper-grid" aria-hidden="true">
            <div class="cg-v l1"></div><div class="cg-v l2"></div>
            <div class="cg-h l1"></div><div class="cg-h l2"></div>
            <div class="cg-border"></div>
          </div>
        </div>
      </div>

      <div class="cropper-controls">
        <label class="cropper-zoom">
          <span class="muted text-xs font-semibold">Priartinimas</span>
          <input
            type="range"
            :min="minScale"
            :max="maxScale"
            step="0.001"
            :value="scale"
            @input="onZoomSlider"
          />
        </label>
        <div class="cropper-actions">
          <button class="btn" type="button" @click="onCancel">Atšaukti</button>
          <button class="btn btn-primary" type="button" :disabled="!imageReady" @click="onSave">
            Išsaugoti
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
  file: { type: File, required: true },
  aspectRatio: { type: Number, default: 1 },
  outputWidth: { type: Number, default: 1000 },
  outputType: { type: String, default: 'image/jpeg' },
  quality: { type: Number, default: 0.9 },
});

const emit = defineEmits(['save', 'cancel']);

const imageSrc = ref('');
const viewportEl = ref(null);
const imgEl = ref(null);
const imgNatural = ref({ w: 0, h: 0 });
const viewportSize = ref({ w: 0, h: 0 });

const tx = ref(0);
const ty = ref(0);
const scale = ref(1);
const minScale = ref(0.01);
const maxScale = ref(10);
const imageReady = ref(false);

let dragStart = null;
let ro = null;

const imgStyle = computed(() => ({
  width: imgNatural.value.w + 'px',
  height: imgNatural.value.h + 'px',
  transform: `translate(${tx.value}px, ${ty.value}px) scale(${scale.value})`,
  transformOrigin: '0 0',
}));

onMounted(() => {
  imageSrc.value = URL.createObjectURL(props.file);
  measureViewport();
  ro = new ResizeObserver(() => {
    measureViewport();
    recomputeBounds();
    clampPosition();
  });
  if (viewportEl.value) ro.observe(viewportEl.value);
  document.addEventListener('mousemove', onDragMove);
  document.addEventListener('mouseup', endDrag);
  document.addEventListener('touchmove', onDragMove, { passive: false });
  document.addEventListener('touchend', endDrag);
  document.addEventListener('keydown', onKey);
});

onBeforeUnmount(() => {
  document.removeEventListener('mousemove', onDragMove);
  document.removeEventListener('mouseup', endDrag);
  document.removeEventListener('touchmove', onDragMove);
  document.removeEventListener('touchend', endDrag);
  document.removeEventListener('keydown', onKey);
  if (ro) ro.disconnect();
  if (imageSrc.value) URL.revokeObjectURL(imageSrc.value);
});

function onKey(e) {
  if (e.key === 'Escape') onCancel();
}

function measureViewport() {
  if (!viewportEl.value) return;
  const r = viewportEl.value.getBoundingClientRect();
  viewportSize.value = { w: r.width, h: r.height };
}

function onImgLoaded() {
  if (!imgEl.value) return;
  imgNatural.value = { w: imgEl.value.naturalWidth, h: imgEl.value.naturalHeight };
  measureViewport();
  recomputeBounds();
  const s = minScale.value;
  scale.value = s;
  tx.value = (viewportSize.value.w - imgNatural.value.w * s) / 2;
  ty.value = (viewportSize.value.h - imgNatural.value.h * s) / 2;
  imageReady.value = true;
}

function recomputeBounds() {
  const { w: iw, h: ih } = imgNatural.value;
  const { w: vw, h: vh } = viewportSize.value;
  if (!iw || !ih || !vw || !vh) return;
  const cover = Math.max(vw / iw, vh / ih);
  minScale.value = cover;
  maxScale.value = cover * 6;
  if (scale.value < minScale.value) scale.value = minScale.value;
  if (scale.value > maxScale.value) scale.value = maxScale.value;
}

function clampPosition() {
  const { w: iw, h: ih } = imgNatural.value;
  const { w: vw, h: vh } = viewportSize.value;
  const s = scale.value;
  const drawnW = iw * s;
  const drawnH = ih * s;
  const minTx = vw - drawnW;
  const minTy = vh - drawnH;
  if (tx.value > 0) tx.value = 0;
  if (tx.value < minTx) tx.value = minTx;
  if (ty.value > 0) ty.value = 0;
  if (ty.value < minTy) ty.value = minTy;
}

function getPointer(e) {
  if (e.touches && e.touches[0]) return { x: e.touches[0].clientX, y: e.touches[0].clientY };
  return { x: e.clientX, y: e.clientY };
}

function startDrag(e) {
  if (!imageReady.value) return;
  const p = getPointer(e);
  dragStart = { x: p.x, y: p.y, tx: tx.value, ty: ty.value };
  e.preventDefault();
}
function onDragMove(e) {
  if (!dragStart) return;
  const p = getPointer(e);
  tx.value = dragStart.tx + (p.x - dragStart.x);
  ty.value = dragStart.ty + (p.y - dragStart.y);
  clampPosition();
  e.preventDefault();
}
function endDrag() {
  dragStart = null;
}

function onWheel(e) {
  if (!imageReady.value) return;
  const rect = viewportEl.value.getBoundingClientRect();
  const px = e.clientX - rect.left;
  const py = e.clientY - rect.top;
  zoomAround(px, py, e.deltaY < 0 ? 1.08 : 1 / 1.08);
}

function onZoomSlider(e) {
  const newScale = Math.min(maxScale.value, Math.max(minScale.value, parseFloat(e.target.value)));
  const cx = viewportSize.value.w / 2;
  const cy = viewportSize.value.h / 2;
  applyScale(newScale, cx, cy);
}

function zoomAround(px, py, factor) {
  const newScale = Math.min(maxScale.value, Math.max(minScale.value, scale.value * factor));
  applyScale(newScale, px, py);
}

function applyScale(newScale, px, py) {
  const s0 = scale.value;
  const k = newScale / s0;
  tx.value = px - (px - tx.value) * k;
  ty.value = py - (py - ty.value) * k;
  scale.value = newScale;
  clampPosition();
}

watch(scale, clampPosition);

function onCancel() {
  emit('cancel');
}

async function onSave() {
  if (!imageReady.value) return;
  const { w: vw, h: vh } = viewportSize.value;
  const s = scale.value;
  const sx = Math.max(0, -tx.value / s);
  const sy = Math.max(0, -ty.value / s);
  const sw = vw / s;
  const sh = vh / s;

  const outW = Math.round(props.outputWidth);
  const outH = Math.round(outW / props.aspectRatio);

  const canvas = document.createElement('canvas');
  canvas.width = outW;
  canvas.height = outH;
  const ctx = canvas.getContext('2d');
  ctx.imageSmoothingQuality = 'high';
  if (props.outputType === 'image/jpeg') {
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, outW, outH);
  }
  ctx.drawImage(imgEl.value, sx, sy, sw, sh, 0, 0, outW, outH);

  const blob = await new Promise((resolve) =>
    canvas.toBlob(resolve, props.outputType, props.quality)
  );
  if (!blob) {
    emit('cancel');
    return;
  }
  const ext = props.outputType === 'image/png' ? 'png' : 'jpg';
  const base = (props.file.name || 'nuotrauka').replace(/\.[^.]+$/, '');
  const file = new File([blob], `${base}.${ext}`, { type: props.outputType });
  emit('save', file);
}
</script>

<style scoped>
.cropper-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.72);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 16px;
}
.cropper-modal {
  background: var(--surface);
  color: var(--text);
  border: 2px solid var(--border);
  border-radius: 16px;
  width: min(760px, 100%);
  max-height: calc(100vh - 32px);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: var(--shadow-lg);
}
.cropper-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px;
  border-bottom: 2px solid var(--border);
}
.cropper-title {
  font-size: 16px;
  font-weight: 600;
  margin: 0;
}
.cropper-x {
  background: transparent;
  border: 0;
  font-size: 22px;
  line-height: 1;
  cursor: pointer;
  color: var(--text);
  padding: 4px 10px;
  border-radius: 10px;
}
.cropper-x:hover {
  background: var(--surface-2);
}
.cropper-stage {
  padding: 16px;
  background: var(--surface-2);
  display: flex;
  align-items: center;
  justify-content: center;
}
.cropper-viewport {
  position: relative;
  width: 100%;
  max-width: 560px;
  background: #000;
  border-radius: 8px;
  overflow: hidden;
  cursor: grab;
  touch-action: none;
  user-select: none;
}
.cropper-viewport:active {
  cursor: grabbing;
}
.cropper-img {
  position: absolute;
  top: 0;
  left: 0;
  max-width: none;
  pointer-events: none;
}
.cropper-grid {
  position: absolute;
  inset: 0;
  pointer-events: none;
}
.cg-border {
  position: absolute;
  inset: 0;
  border: 2px solid rgba(255, 255, 255, 0.9);
  box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.15) inset;
}
.cg-v, .cg-h {
  position: absolute;
  background: rgba(255, 255, 255, 0.35);
}
.cg-v {
  top: 0;
  bottom: 0;
  width: 1px;
}
.cg-v.l1 { left: 33.3333%; }
.cg-v.l2 { left: 66.6666%; }
.cg-h {
  left: 0;
  right: 0;
  height: 1px;
}
.cg-h.l1 { top: 33.3333%; }
.cg-h.l2 { top: 66.6666%; }

.cropper-controls {
  padding: 14px 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  border-top: 2px solid var(--border);
}
.cropper-zoom {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.cropper-zoom input[type='range'] {
  width: 100%;
}
.cropper-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}
</style>
