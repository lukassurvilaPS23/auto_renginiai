<template>
  <div>
    <div v-if="editable" class="toolbar">
      <label class="tool">
        Spalva:
        <input v-model="drawColor" class="h-9 w-14 rounded-lg border" :style="{ borderColor: 'var(--border)' }" type="color" />
      </label>
    </div>
    <div ref="mapEl" class="map"></div>
    <p v-if="help" class="muted mt-3 text-sm">{{ help }}</p>
  </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount, ref, watch } from 'vue';
import L from 'leaflet';
import 'leaflet-draw';

const props = defineProps({
  modelValue: { type: Object, default: null },
  center: { type: Object, default: () => ({ lat: 54.6872, lng: 25.2797 }) },
  editable: { type: Boolean, default: false },
  help: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue', 'update:center']);

const mapEl = ref(null);
const drawColor = ref('#2f8f82');
let map;
let drawn;
let marker;

function toLatLng(c) {
  const lat = Number(c?.lat);
  const lng = Number(c?.lng);
  if (Number.isFinite(lat) && Number.isFinite(lng)) return { lat, lng };
  return { lat: 54.6872, lng: 25.2797 };
}

function emitValue() {
  const geojson = drawn ? drawn.toGeoJSON() : null;
  const center = marker ? marker.getLatLng() : null;
  emit('update:modelValue', geojson);
  if (center) emit('update:center', { lat: center.lat, lng: center.lng });
}

function applyStyleToLayer(layer, color) {
  if (!layer) return;
  if (typeof layer.setStyle === 'function') {
    layer.setStyle({ color, weight: 4, opacity: 0.95 });
  }
}

function setFeatureStyle(layer, feature) {
  const color = feature?.properties?.style?.color;
  if (color) {
    applyStyleToLayer(layer, color);
  }
}

function setFromValue(value) {
  if (!map || !drawn) return;
  drawn.clearLayers();
  if (value && value.type === 'FeatureCollection') {
    const layer = L.geoJSON(value, {
      style: (feature) => {
        const color = feature?.properties?.style?.color;
        return color ? { color, weight: 4, opacity: 0.95 } : undefined;
      },
      onEachFeature: (feature, l) => {
        setFeatureStyle(l, feature);
      },
    });
    layer.eachLayer((l) => drawn.addLayer(l));
    try {
      const b = layer.getBounds();
      if (b.isValid()) map.fitBounds(b.pad(0.2));
    } catch (_) {}
  }
}

onMounted(() => {
  const c = toLatLng(props.center);

  map = L.map(mapEl.value).setView([c.lat, c.lng], 12);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
  }).addTo(map);

  drawn = new L.FeatureGroup();
  map.addLayer(drawn);

  marker = L.marker([c.lat, c.lng], { draggable: !!props.editable }).addTo(map);

  if (props.editable) {
    marker.on('dragend', emitValue);

    const baseStyle = () => ({
      color: drawColor.value,
      weight: 4,
      opacity: 0.95,
      fillColor: drawColor.value,
      fillOpacity: 0.18,
    });

    const drawControl = new L.Control.Draw({
      edit: {
        featureGroup: drawn,
        remove: true,
      },
      draw: {
        polygon: { shapeOptions: baseStyle() },
        polyline: { shapeOptions: baseStyle() },
        rectangle: { shapeOptions: baseStyle() },
        circle: false,
        circlemarker: false,
        marker: false,
      },
    });
    map.addControl(drawControl);

    map.on(L.Draw.Event.CREATED, (e) => {
      const color = drawColor.value;
      applyStyleToLayer(e.layer, color);
      if (typeof e.layer.toGeoJSON === 'function') {
        e.layer.feature = e.layer.feature || { type: 'Feature', properties: {} };
        e.layer.feature.properties = e.layer.feature.properties || {};
        e.layer.feature.properties.style = { color };
      }
      drawn.addLayer(e.layer);
      emitValue();
    });

    map.on(L.Draw.Event.EDITED, (e) => {
      e.layers.eachLayer((layer) => {
        const color = layer?.feature?.properties?.style?.color;
        if (color) applyStyleToLayer(layer, color);
      });
      emitValue();
    });
    map.on(L.Draw.Event.DELETED, () => emitValue());

    map.on('click', (e) => {
      marker.setLatLng(e.latlng);
      emitValue();
    });
  }

  setFromValue(props.modelValue);
  emitValue();
});

onBeforeUnmount(() => {
  if (map) {
    map.remove();
    map = null;
  }
});

watch(
  () => props.modelValue,
  (v) => {
    setFromValue(v);
  }
);
</script>

<style scoped>
.toolbar {
  display: flex;
  gap: 12px;
  align-items: center;
  margin-bottom: 10px;
}
.tool {
  display: flex;
  gap: 8px;
  align-items: center;
}
.map {
  width: 100%;
  height: 360px;
  border: 2px solid var(--border);
  border-radius: 12px;
}
</style>
