<script setup>
import { ref, shallowRef, onMounted, onUnmounted, watch } from 'vue';
import * as maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';

const props = defineProps({
  markers: {
    type: Array,
    default: () => [],
  },
  initialCenter: {
    type: Array,
    default: () => [106.8272, -6.1751], // Jakarta [lng, lat]
  },
  initialZoom: {
    type: Number,
    default: 3.5,
  },
  initialPitch: {
    type: Number,
    default: 35,
  },
  initialBearing: {
    type: Number,
    default: 0,
  },
  height: {
    type: String,
    default: '540px',
  },
  styleType: {
    type: String,
    default: 'auto', // 'auto' | 'dark' | 'light' | 'voyager' | 'osm'
  },
});

const emit = defineEmits(['selectMarker', 'mapMove', 'mapLoaded']);

const mapContainer = ref(null);
const mapInstance = shallowRef(null);
const currentStyleKey = ref('dark');
const activeMarkerId = ref(null);
let markerInstances = [];
let resizeObserver = null;
let themeObserver = null;
let orbitInterval = null;

// Helper to create reliable raster style specifications (Fast, zero glyph/worker network failures)
const createRasterStyle = (subdomains, tileTemplate, attribution) => ({
  version: 8,
  sources: {
    'raster-tiles': {
      type: 'raster',
      tiles: subdomains.map(s => tileTemplate.replace('{s}', s)),
      tileSize: 256,
      attribution: attribution || '&copy; OpenStreetMap contributors &copy; CARTO',
    },
  },
  layers: [
    {
      id: 'raster-tiles-layer',
      type: 'raster',
      source: 'raster-tiles',
      minzoom: 0,
      maxzoom: 19,
    },
  ],
});

// Style definitions using Carto and OpenStreetMap CDN raster tiles
const STYLES = {
  dark: createRasterStyle(
    ['a', 'b', 'c', 'd'],
    'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}.png',
    '&copy; OpenStreetMap &copy; CARTO'
  ),
  light: createRasterStyle(
    ['a', 'b', 'c', 'd'],
    'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png',
    '&copy; OpenStreetMap &copy; CARTO'
  ),
  voyager: createRasterStyle(
    ['a', 'b', 'c', 'd'],
    'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png',
    '&copy; OpenStreetMap &copy; CARTO'
  ),
  osm: createRasterStyle(
    ['tile'],
    'https://{s}.openstreetmap.org/{z}/{x}/{y}.png',
    '&copy; OpenStreetMap contributors'
  ),
};

const isDarkMode = () => {
  if (typeof document === 'undefined') return false;
  return document.documentElement.classList.contains('dark');
};

const resolveStyle = (styleKey) => {
  if (styleKey === 'auto') {
    return isDarkMode() ? STYLES.dark : STYLES.light;
  }
  return STYLES[styleKey] || STYLES.dark;
};

// Clear all markers from map canvas
const clearMarkers = () => {
  markerInstances.forEach(marker => marker.remove());
  markerInstances = [];
};

// Render custom HTML Markers with glowing animated radar rings & interactive popups
const renderMarkers = () => {
  if (!mapInstance.value) return;
  clearMarkers();

  props.markers.forEach((node) => {
    if (!node.coordinates || node.coordinates.length < 2) return;

    // Marker DOM Element
    const el = document.createElement('div');
    el.className = 'maplibre-custom-marker';
    el.style.width = '38px';
    el.style.height = '38px';
    el.style.cursor = 'pointer';
    el.style.zIndex = '15';
    el.setAttribute('data-node-id', node.id);

    const isPrimary = node.id === 'jkt-01';

    el.innerHTML = `
      <div style="position: relative; width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;">
        <span style="position: absolute; width: 100%; height: 100%; border-radius: 9999px; opacity: 0.75; animation: maplibre-pulse-ping 1.5s cubic-bezier(0, 0, 0.2, 1) infinite; background-color: ${isPrimary ? '#6366f1' : '#10b981'};"></span>
        <div style="position: relative; z-index: 10; display: flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 9999px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.5); border: 2px solid #ffffff; background-color: ${isPrimary ? '#4f46e5' : '#059669'}; color: #ffffff; font-size: 13px; font-weight: bold; cursor: pointer; transition: transform 0.2s;">
          ${node.flag || '📍'}
        </div>
      </div>
    `;

    // Interactive HTML Popup
    const popup = new maplibregl.Popup({
      offset: 22,
      closeButton: true,
      closeOnClick: false,
      className: 'custom-maplibre-popup',
    }).setHTML(`
      <div style="padding: 12px; font-family: sans-serif; color: #0f172a; min-width: 220px;">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 8px;">
          <div style="display: flex; align-items: center; gap: 6px; font-weight: bold; font-size: 12px; color: #0f172a;">
            <span>${node.flag || '🌐'}</span>
            <span>${node.name}</span>
          </div>
          <span style="padding: 2px 6px; border-radius: 9999px; font-size: 10px; font-weight: bold; text-transform: uppercase; background-color: #d1fae5; color: #065f46;">
            ${node.status}
          </span>
        </div>
        <div style="font-size: 11px; color: #475569; display: flex; flex-direction: column; gap: 4px; margin-bottom: 8px;">
          <div style="display: flex; justify-content: space-between;">
            <span style="color: #64748b;">Peran Node:</span>
            <span style="font-weight: 500; color: #1e293b;">${node.type || 'Edge Cluster'}</span>
          </div>
          <div style="display: flex; justify-content: space-between;">
            <span style="color: #64748b;">Ping Latency:</span>
            <span style="font-weight: bold; color: #059669;">${node.ping || '4ms'}</span>
          </div>
          <div style="display: flex; justify-content: space-between;">
            <span style="color: #64748b;">Koneksi Aktif:</span>
            <span style="font-family: monospace; font-weight: 600; color: #4f46e5;">${node.connections ? Number(node.connections).toLocaleString() : '1420'} req/s</span>
          </div>
          <div style="font-size: 10px; color: #94a3b8; margin-top: 4px;">
            📍 ${node.location || ''}
          </div>
        </div>
      </div>
    `);

    const marker = new maplibregl.Marker({ element: el })
      .setLngLat(node.coordinates)
      .setPopup(popup)
      .addTo(mapInstance.value);

    el.addEventListener('click', () => {
      activeMarkerId.value = node.id;
      emit('selectMarker', node);
    });

    markerInstances.push(marker);
  });
};

const initMap = () => {
  if (!mapContainer.value) return;

  if (mapInstance.value) {
    mapInstance.value.remove();
    mapInstance.value = null;
  }

  const initialStyle = resolveStyle(props.styleType);
  currentStyleKey.value = props.styleType === 'auto' ? (isDarkMode() ? 'dark' : 'light') : props.styleType;

  const map = new maplibregl.Map({
    container: mapContainer.value,
    style: initialStyle,
    center: props.initialCenter,
    zoom: props.initialZoom,
    pitch: props.initialPitch,
    bearing: props.initialBearing,
    attributionControl: false,
  });

  // Controls
  map.addControl(new maplibregl.NavigationControl({ visualizePitch: true }), 'top-right');
  map.addControl(new maplibregl.FullscreenControl(), 'top-right');
  map.addControl(new maplibregl.ScaleControl({ unit: 'metric' }), 'bottom-left');
  map.addControl(
    new maplibregl.AttributionControl({
      compact: true,
      customAttribution: 'MapLibre GL JS • Carto / OSM',
    }),
    'bottom-right'
  );

  map.on('load', () => {
    renderMarkers();
    emit('mapLoaded', map);
  });

  map.on('move', () => {
    const center = map.getCenter();
    emit('mapMove', {
      lng: Number(center.lng.toFixed(4)),
      lat: Number(center.lat.toFixed(4)),
      zoom: Number(map.getZoom().toFixed(2)),
      pitch: Number(map.getPitch().toFixed(1)),
      bearing: Number(map.getBearing().toFixed(1)),
    });
  });

  mapInstance.value = map;
  // Render markers immediately
  renderMarkers();
};

// API Exposes for Parent Component
const flyTo = (coords, zoom = 12, pitch = 45, bearing = 0) => {
  if (!mapInstance.value) return;
  mapInstance.value.flyTo({
    center: coords,
    zoom,
    pitch,
    bearing,
    essential: true,
    speed: 1.2,
    curve: 1.4,
  });
};

const setStyle = (styleKey) => {
  if (!mapInstance.value) return;
  currentStyleKey.value = styleKey;
  const style = resolveStyle(styleKey);
  mapInstance.value.setStyle(style);
  mapInstance.value.once('style.load', () => {
    renderMarkers();
  });
};

const resetView = () => {
  flyTo(props.initialCenter, props.initialZoom, props.initialPitch, props.initialBearing);
};

const toggle3DPitch = () => {
  if (!mapInstance.value) return;
  const currentPitch = mapInstance.value.getPitch();
  const nextPitch = currentPitch > 20 ? 0 : 60;
  mapInstance.value.easeTo({ pitch: nextPitch, duration: 600 });
};

let isOrbiting = false;
const toggleOrbit = () => {
  if (!mapInstance.value) return false;
  if (isOrbiting) {
    if (orbitInterval) clearInterval(orbitInterval);
    orbitInterval = null;
    isOrbiting = false;
    return false;
  } else {
    isOrbiting = true;
    orbitInterval = setInterval(() => {
      if (!mapInstance.value) return;
      const currentBearing = mapInstance.value.getBearing();
      mapInstance.value.setBearing((currentBearing + 1) % 360);
    }, 50);
    return true;
  }
};

onMounted(() => {
  initMap();

  // ResizeObserver for clean auto-resizing
  if (typeof ResizeObserver !== 'undefined' && mapContainer.value) {
    resizeObserver = new ResizeObserver(() => {
      if (mapInstance.value) {
        mapInstance.value.resize();
      }
    });
    resizeObserver.observe(mapContainer.value);
  }

  // Theme Observer for Dark / Light Mode
  if (typeof MutationObserver !== 'undefined' && typeof document !== 'undefined') {
    themeObserver = new MutationObserver((mutations) => {
      for (const mutation of mutations) {
        if (mutation.attributeName === 'class' && props.styleType === 'auto') {
          const targetKey = isDarkMode() ? 'dark' : 'light';
          if (targetKey !== currentStyleKey.value) {
            setStyle('auto');
          }
          break;
        }
      }
    });
    themeObserver.observe(document.documentElement, { attributes: true });
  }
});

watch(() => props.markers, () => {
  renderMarkers();
}, { deep: true });

watch(() => props.styleType, (newVal) => {
  setStyle(newVal);
});

onUnmounted(() => {
  if (orbitInterval) {
    clearInterval(orbitInterval);
    orbitInterval = null;
  }
  if (resizeObserver) {
    resizeObserver.disconnect();
    resizeObserver = null;
  }
  if (themeObserver) {
    themeObserver.disconnect();
    themeObserver = null;
  }
  clearMarkers();
  if (mapInstance.value) {
    mapInstance.value.remove();
    mapInstance.value = null;
  }
});

defineExpose({
  getMapInstance: () => mapInstance.value,
  flyTo,
  setStyle,
  resetView,
  toggle3DPitch,
  toggleOrbit,
  renderMarkers,
});
</script>

<template>
  <div class="relative w-full rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-800 shadow-md">
    <div ref="mapContainer" :style="{ height, width: '100%' }" class="w-full"></div>
    <slot name="overlay"></slot>
  </div>
</template>

<style>
@keyframes maplibre-pulse-ping {
  0% {
    transform: scale(0.95);
    opacity: 0.8;
  }
  70% {
    transform: scale(2.2);
    opacity: 0;
  }
  100% {
    transform: scale(2.4);
    opacity: 0;
  }
}

.maplibregl-marker {
  position: absolute;
  top: 0;
  left: 0;
  pointer-events: auto;
  cursor: pointer;
  z-index: 10;
}

.custom-maplibre-popup .maplibregl-popup-content {
  border-radius: 1rem;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.25), 0 8px 10px -6px rgba(0, 0, 0, 0.25);
  padding: 0;
  border: 1px solid rgba(226, 232, 240, 0.8);
  backdrop-filter: blur(8px);
}
.maplibregl-popup-close-button {
  padding: 6px 10px;
  color: #64748b;
  font-size: 14px;
}
.maplibregl-popup-close-button:hover {
  color: #0f172a;
}
</style>
