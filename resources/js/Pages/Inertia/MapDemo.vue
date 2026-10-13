<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import HybridNavbar from '../../Components/HybridNavbar.vue';
import BaseMap from '../../Components/BaseMap.vue';
import {
  Compass,
  MapPin,
  Globe,
  Layers,
  Activity,
  Cpu,
  RotateCw,
  Box,
  Eye,
  Server,
  Zap,
  ArrowUpRight,
  Sparkles,
  ShieldCheck,
  CheckCircle2,
  Navigation
} from 'lucide-vue-next';

const props = defineProps({
  nodes: {
    type: Array,
    required: true,
  },
  summary: {
    type: Object,
    default: () => ({
      total_nodes: 6,
      active_connections: 4020,
      avg_latency: '78ms',
      global_uptime: '99.99%',
    }),
  },
});

const mapRef = ref(null);
const selectedStyle = ref('auto');
const activeNode = ref(props.nodes[0] || null);
const isOrbiting = ref(false);
const is3DActive = ref(true);

const liveCoordinates = ref({
  lng: 106.8272,
  lat: -6.1751,
  zoom: 3.2,
  pitch: 35,
  bearing: 0,
});

const handleMapMove = (coords) => {
  liveCoordinates.value = coords;
};

const handleSelectMarker = (node) => {
  activeNode.value = node;
};

const flyToNode = (node) => {
  activeNode.value = node;
  if (mapRef.value && node.coordinates) {
    mapRef.value.flyTo(node.coordinates, 12.5, 50, 20);
  }
};

const flyToGlobal = () => {
  activeNode.value = null;
  if (mapRef.value) {
    mapRef.value.flyTo([20, 15], 1.8, 0, 0);
  }
};

const togglePitch = () => {
  if (mapRef.value) {
    mapRef.value.toggle3DPitch();
    is3DActive.value = !is3DActive.value;
  }
};

const toggleOrbit = () => {
  if (mapRef.value) {
    isOrbiting.value = mapRef.value.toggleOrbit();
  }
};

const changeStyle = (styleKey) => {
  selectedStyle.value = styleKey;
  if (mapRef.value) {
    mapRef.value.setStyle(styleKey);
  }
};

const getUrl = (path) => {
  if (typeof window !== 'undefined' && window.location.pathname.includes('/hybridlaravel/public')) {
    return `/hybridlaravel/public${path}`;
  }
  return path;
};
</script>

<template>
  <Head title="MapLibre GL JS Geospatial Demo - Hybrid Laravel" />

  <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 selection:bg-indigo-500 selection:text-white transition-colors duration-200">
    <HybridNavbar mode="inertia" currentRoute="map" />

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
      
      <!-- Top Banner -->
      <div class="relative overflow-hidden bg-gradient-to-r from-teal-900/90 via-indigo-900/80 to-slate-950 border border-teal-700/40 rounded-3xl p-6 sm:p-8 backdrop-blur-md shadow-xl text-white">
        <div class="absolute -right-12 -top-12 w-64 h-64 bg-teal-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute right-1/3 -bottom-10 w-48 h-48 bg-indigo-500/10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
          <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-mono font-medium bg-teal-950/90 text-teal-300 border border-teal-700/50 mb-3 shadow-inner">
              <span class="w-2 h-2 rounded-full bg-teal-400 animate-pulse"></span>
              <span>MapLibre GL JS • WebGL Vector Tiles • Hardware Accelerated</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight flex items-center gap-2.5">
              <span>Geospatial Edge Telemetry Demo</span>
              <Sparkles class="w-6 h-6 text-amber-400 animate-bounce" />
            </h1>
            <p class="mt-2 text-slate-300 text-sm max-w-2xl leading-relaxed">
              Visualisasi distribusi global edge cluster dan database nodes secara real-time dengan WebGL interaktif MapLibre GL JS.
              Mendukung navigasi 3D tilt, pergantian layer peta dinamis, dan integrasi mulus dark/light mode.
            </p>
          </div>

          <div class="flex flex-wrap items-center gap-3">
            <a :href="getUrl('/inertia/dashboard')" class="px-5 py-2.5 rounded-xl font-semibold text-white bg-indigo-600 hover:bg-indigo-500 transition-all text-sm shadow-lg shadow-indigo-600/30 flex items-center gap-2">
              <span>← Kembali ke Dashboard</span>
            </a>
            <a :href="getUrl('/spa')" class="px-5 py-2.5 rounded-xl font-semibold text-slate-200 bg-slate-900/90 hover:bg-slate-800 border border-slate-700 transition-all text-sm flex items-center gap-1.5">
              <span>SPA Mode</span>
              <ArrowUpRight class="w-4 h-4 text-slate-400" />
            </a>
          </div>
        </div>
      </div>

      <!-- Quick Telemetry Metrics Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm dark:shadow-none hover:border-teal-300 dark:hover:border-teal-700/60 transition-all">
          <div class="flex items-center justify-between text-xs font-mono text-teal-600 dark:text-teal-400 uppercase tracking-wider">
            <span>Global Edge Nodes</span>
            <Globe class="w-4 h-4 text-teal-500" />
          </div>
          <div class="text-3xl font-extrabold text-slate-900 dark:text-white mt-2">{{ summary.total_nodes }} Nodes</div>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1">
            <CheckCircle2 class="w-3.5 h-3.5 text-emerald-500 inline" /> 100% Operational Status
          </p>
        </div>

        <div class="bg-white dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm dark:shadow-none hover:border-indigo-300 dark:hover:border-indigo-700/60 transition-all">
          <div class="flex items-center justify-between text-xs font-mono text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">
            <span>Active Streams / Sockets</span>
            <Zap class="w-4 h-4 text-indigo-500" />
          </div>
          <div class="text-3xl font-extrabold text-indigo-600 dark:text-indigo-300 mt-2">{{ Number(summary.active_connections).toLocaleString() }}</div>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Reverb WebSocket Channels</p>
        </div>

        <div class="bg-white dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm dark:shadow-none hover:border-emerald-300 dark:hover:border-emerald-700/60 transition-all">
          <div class="flex items-center justify-between text-xs font-mono text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">
            <span>Rata-Rata Latensi Global</span>
            <Activity class="w-4 h-4 text-emerald-500" />
          </div>
          <div class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-300 mt-2">{{ summary.avg_latency }}</div>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Sub-second Cross-Region Ping</p>
        </div>

        <div class="bg-white dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm dark:shadow-none hover:border-amber-300 dark:hover:border-amber-700/60 transition-all">
          <div class="flex items-center justify-between text-xs font-mono text-amber-600 dark:text-amber-400 uppercase tracking-wider">
            <span>Availability SLA</span>
            <ShieldCheck class="w-4 h-4 text-amber-500" />
          </div>
          <div class="text-3xl font-extrabold text-amber-600 dark:text-amber-300 mt-2">{{ summary.global_uptime }}</div>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">High Availability Redundancy</p>
        </div>
      </div>

      <!-- Main Map Showcase Section -->
      <div class="bg-white dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800/80 rounded-3xl p-5 sm:p-6 shadow-sm dark:shadow-none backdrop-blur-sm space-y-5">
        
        <!-- Controls Bar -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-800/80">
          
          <!-- Style Selectors -->
          <div class="flex items-center flex-wrap gap-2">
            <span class="text-xs font-mono text-slate-500 dark:text-slate-400 mr-1 flex items-center gap-1.5">
              <Layers class="w-3.5 h-3.5 text-teal-500" /> Tema Peta:
            </span>
            <button
              v-for="st in [
                { id: 'auto', label: 'Auto (Tema OS)' },
                { id: 'dark', label: 'Dark Matter' },
                { id: 'light', label: 'Positron' },
                { id: 'voyager', label: 'Voyager' },
                { id: 'osm', label: 'OpenStreetMap' }
              ]"
              :key="st.id"
              type="button"
              @click="changeStyle(st.id)"
              class="px-3 py-1.5 rounded-xl text-xs font-medium transition-all cursor-pointer"
              :class="selectedStyle === st.id
                ? 'bg-teal-600 text-white font-semibold shadow-sm'
                : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'"
            >
              {{ st.label }}
            </button>
          </div>

          <!-- Camera Tools (Fly To, Orbit, 3D Pitch) -->
          <div class="flex items-center flex-wrap gap-2">
            <button
              type="button"
              @click="flyToGlobal"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-medium transition-all cursor-pointer"
              title="Pusatkan kamera ke tampilan dunia"
            >
              <Globe class="w-3.5 h-3.5 text-teal-500" />
              <span>Tampilan Global</span>
            </button>

            <button
              type="button"
              @click="togglePitch"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-medium transition-all cursor-pointer"
              title="Ubah sudut kemiringan kamera 3D"
            >
              <Box class="w-3.5 h-3.5 text-indigo-500" />
              <span>Kemiringan 3D</span>
            </button>

            <button
              type="button"
              @click="toggleOrbit"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium transition-all cursor-pointer"
              :class="isOrbiting 
                ? 'bg-amber-500 text-white font-semibold shadow-sm animate-pulse' 
                : 'bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200'"
              title="Putar kamera secara otomatis mengelilingi titik aktif"
            >
              <RotateCw class="w-3.5 h-3.5" :class="{ 'animate-spin': isOrbiting }" />
              <span>{{ isOrbiting ? 'Stop Orbit' : 'Auto Orbit' }}</span>
            </button>
          </div>

        </div>

        <!-- Quick Fly-to Preset Cities -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
          <span class="text-xs font-mono text-slate-400 whitespace-nowrap">Fly to Node:</span>
          <button
            v-for="node in nodes"
            :key="node.id"
            type="button"
            @click="flyToNode(node)"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium whitespace-nowrap transition-all cursor-pointer border"
            :class="activeNode?.id === node.id
              ? 'bg-indigo-50 dark:bg-indigo-950/80 border-indigo-400 dark:border-indigo-700 text-indigo-700 dark:text-indigo-300 font-semibold'
              : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:border-slate-300 dark:hover:border-slate-700'"
          >
            <span>{{ node.flag }}</span>
            <span>{{ node.name.split(' ')[0] }}</span>
            <span class="text-[10px] font-mono text-emerald-500">({{ node.ping }})</span>
          </button>
        </div>

        <!-- Interactive Map Container with Overlay Telemetry HUD -->
        <div class="relative">
          <BaseMap
            ref="mapRef"
            :markers="nodes"
            :style-type="selectedStyle"
            height="580px"
            @select-marker="handleSelectMarker"
            @map-move="handleMapMove"
          >
            <template #overlay>
              <!-- Live Telemetry Coordinates HUD (Top-Left Floating) -->
              <div class="absolute top-3 left-3 z-10 bg-slate-900/85 text-white border border-slate-700/80 rounded-xl p-3 shadow-xl backdrop-blur-md text-[11px] font-mono space-y-1 pointer-events-none hidden sm:block max-w-xs">
                <div class="flex items-center gap-1.5 text-teal-400 font-bold border-b border-slate-700/80 pb-1 mb-1">
                  <Navigation class="w-3.5 h-3.5" />
                  <span>Real-Time WebGL HUD</span>
                </div>
                <div class="flex justify-between gap-4">
                  <span class="text-slate-400">Lng / Lat:</span>
                  <span class="text-slate-200">{{ liveCoordinates.lng }}, {{ liveCoordinates.lat }}</span>
                </div>
                <div class="flex justify-between gap-4">
                  <span class="text-slate-400">Zoom / Pitch:</span>
                  <span class="text-slate-200">{{ liveCoordinates.zoom }}x / {{ liveCoordinates.pitch }}°</span>
                </div>
                <div class="flex justify-between gap-4">
                  <span class="text-slate-400">Bearing:</span>
                  <span class="text-slate-200">{{ liveCoordinates.bearing }}°</span>
                </div>
              </div>

              <!-- Active Selected Node Details (Bottom-Right Floating) -->
              <div v-if="activeNode" class="absolute bottom-3 left-3 sm:left-auto sm:right-3 z-10 bg-white/95 dark:bg-slate-900/95 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-2xl backdrop-blur-md max-w-sm">
                <div class="flex items-center justify-between gap-3 mb-2">
                  <div class="flex items-center gap-2">
                    <span class="text-xl">{{ activeNode.flag }}</span>
                    <div>
                      <h4 class="text-xs font-bold text-slate-900 dark:text-white leading-tight">{{ activeNode.name }}</h4>
                      <p class="text-[10px] text-slate-500 dark:text-slate-400 leading-tight">{{ activeNode.type }}</p>
                    </div>
                  </div>
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                    {{ activeNode.status }}
                  </span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-[11px] font-mono border-t border-slate-100 dark:border-slate-800 pt-2 text-slate-600 dark:text-slate-300">
                  <div>
                    <span class="text-[10px] text-slate-400 block">Ping Latency:</span>
                    <span class="font-bold text-emerald-500">{{ activeNode.ping }}</span>
                  </div>
                  <div>
                    <span class="text-[10px] text-slate-400 block">Throughput:</span>
                    <span class="font-bold text-indigo-500">{{ activeNode.connections }} req/s</span>
                  </div>
                </div>
                <p class="text-[10px] text-slate-400 truncate mt-2 font-mono">📍 {{ activeNode.location }}</p>
              </div>
            </template>
          </BaseMap>
        </div>

      </div>

      <!-- Edge Cluster Detail Table -->
      <div class="bg-white dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800/80 rounded-3xl p-6 sm:p-8 shadow-sm dark:shadow-none backdrop-blur-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800/80 mb-6 gap-2">
          <div>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
              <span>Inventaris Node Server & Edge Relays</span>
              <span class="text-xs font-normal px-2 py-0.5 rounded-md bg-teal-100 dark:bg-teal-950 text-teal-700 dark:text-teal-300 border border-teal-200 dark:border-teal-800">
                GeoJSON Data
              </span>
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">
              Daftar lokasi koordinat data center yang terhubung dengan sistem hybrid Laravel.
            </p>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
            <thead class="text-xs uppercase bg-slate-50 dark:bg-slate-950/70 text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
              <tr>
                <th class="px-4 py-3">Node & Region</th>
                <th class="px-4 py-3">Peran Arsitektur</th>
                <th class="px-4 py-3">Koordinat (Lng, Lat)</th>
                <th class="px-4 py-3">Waktu Tanggap</th>
                <th class="px-4 py-3">Koneksi Sockets</th>
                <th class="px-4 py-3 text-right">Aksi Kamera</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-mono text-xs">
              <tr v-for="node in nodes" :key="node.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                <td class="px-4 py-3.5 font-sans font-medium text-slate-900 dark:text-white flex items-center gap-2">
                  <span class="text-base">{{ node.flag }}</span>
                  <div>
                    <span class="block font-semibold">{{ node.name }}</span>
                    <span class="block text-[11px] text-slate-400 font-mono">{{ node.location }}</span>
                  </div>
                </td>
                <td class="px-4 py-3.5 text-slate-600 dark:text-slate-300 font-sans">{{ node.type }}</td>
                <td class="px-4 py-3.5 text-slate-500 dark:text-slate-400">
                  [{{ node.coordinates[0] }}, {{ node.coordinates[1] }}]
                </td>
                <td class="px-4 py-3.5">
                  <span class="text-emerald-500 font-semibold">{{ node.ping }}</span>
                </td>
                <td class="px-4 py-3.5 text-indigo-600 dark:text-indigo-400 font-semibold">
                  {{ Number(node.connections).toLocaleString() }} req/s
                </td>
                <td class="px-4 py-3.5 text-right font-sans">
                  <button
                    type="button"
                    @click="flyToNode(node)"
                    class="px-3 py-1 rounded-lg text-xs font-semibold bg-teal-50 dark:bg-teal-950 text-teal-600 dark:text-teal-400 hover:bg-teal-100 dark:hover:bg-teal-900/60 border border-teal-200 dark:border-teal-800 transition-all cursor-pointer"
                  >
                    Fly To ✈
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</template>
